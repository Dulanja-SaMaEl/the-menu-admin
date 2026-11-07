<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class DriverAppOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $driver = $request->user();
        $orders = Order::where('driver_id', $driver->id)
            ->with(['items.food'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'orders' => $orders
        ]);
    }

    public function latest(Request $request): JsonResponse
    {
        $driver = $request->user();
        $orders = Order::where('driver_id', $driver->id)
            ->whereIn('status', ['assigned', 'accepted', 'at_store', 'on_delivery'])
            ->with(['items.food'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'orders' => $orders
        ]);
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $request->validate([
            'status'         => 'required|in:assigned,accepted,at_store,on_delivery,delivered,cancelled',
            'reason'         => 'nullable|string|max:500',
            'notes'          => 'nullable|string|max:1000',
            'pod_notes'      => 'nullable|string|max:1000',
            'recipient_name' => 'nullable|string|max:100',
            'photo_base64'   => 'nullable|string',
            'proof_photo'    => 'nullable|image|max:10240',
        ]);

        $order = Order::where('id', $id)
            ->where('driver_id', $request->user()->id)
            ->firstOrFail();

        $updateData = [
            'status' => $request->status,
        ];

        // Process Proof of Delivery notes / drop-off instructions
        $notes = $request->input('pod_notes', $request->input('notes', $request->input('reason')));
        if (!empty($notes)) {
            $updateData['pod_notes'] = $notes;
        }

        if ($request->filled('recipient_name')) {
            $updateData['recipient_name'] = $request->recipient_name;
        }

        // Process Proof of Delivery photo (file upload or Base64 string from Flutter)
        if ($request->hasFile('proof_photo')) {
            $path = $request->file('proof_photo')->store('pod_photos', 'public');
            $updateData['proof_photo_path'] = $path;
        } elseif ($request->filled('photo_base64')) {
            $rawBase64 = $request->photo_base64;
            if (preg_match('/^data:image\/(\w+);base64,/', $rawBase64, $type)) {
                $rawBase64 = substr($rawBase64, strpos($rawBase64, ',') + 1);
            }
            $imageData = base64_decode($rawBase64);
            if ($imageData !== false && !empty($imageData)) {
                $fileName = 'pod_' . $order->id . '_' . time() . '.jpg';
                Storage::disk('public')->put('pod_photos/' . $fileName, $imageData);
                $updateData['proof_photo_path'] = 'pod_photos/' . $fileName;
            }
        }

        $order->update($updateData);

        $driver = $request->user();
        if ($driver) {
            if (class_exists(\App\Events\DriverStatusUpdated::class)) {
                \App\Events\DriverStatusUpdated::safeDispatch($driver->fresh());
            }
        }

        if (class_exists(\App\Events\OrderStatusUpdated::class)) {
            \App\Events\OrderStatusUpdated::safeDispatch($order->fresh(['user', 'driver', 'items.food']));
        }

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'order'   => $order->fresh(['user', 'driver', 'items.food'])
        ]);
    }
}
