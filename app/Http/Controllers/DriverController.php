<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    /**
     * Display a listing of drivers with summary statistics.
     */
    public function index(Request $request)
    {
        if (auth()->user()?->role === 'Chef') {
            abort(403, 'Unauthorized. Chefs are not allowed to access Driver Management.');
        }

        $search = trim($request->input('search', ''));

        // Global fleet statistics
        $allDrivers = Driver::all();
        $stats = [
            'total'        => $allDrivers->count(),
            'available'    => $allDrivers->where('status', 'available')->count(),
            'on_delivery'  => $allDrivers->where('status', 'on_delivery')->count(),
            'offline'      => $allDrivers->where('status', 'offline')->count(),
        ];

        $query = Driver::with(['assignedOrders' => function ($q) {
            $q->whereIn('status', ['pending', 'processing', 'assigned']);
        }])
        ->withCount([
            'assignedOrders as completed_orders_count' => function ($q) {
                $q->whereIn('status', ['completed', 'delivered']);
            }
        ]);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('vehicle_type', 'like', "%{$search}%")
                  ->orWhere('vehicle_number', 'like', "%{$search}%");
            });
        }

        $drivers = $query->latest()->get();

        return view('drivers', compact('drivers', 'stats', 'search'));
    }

    /**
     * Store a newly created driver.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['nullable', 'email', 'max:255'],
            'phone'          => ['nullable', 'string', 'max:50'],
            'vehicle_type'   => ['nullable', 'string', 'max:50'],
            'vehicle_number' => ['nullable', 'string', 'max:50'],
            'status'         => ['nullable', 'in:available,on_delivery,offline'],
            'max_orders'     => ['nullable', 'integer', 'min:1', 'max:100'],
        ], [
            'name.required' => 'Please enter the driver\'s name.',
            'email.email'   => 'Please enter a valid email address.',
        ]);

        $driver = Driver::create([
            'name'             => $validated['name'],
            'email'            => $validated['email'] ?? null,
            'password'         => \Illuminate\Support\Facades\Hash::make('password123'), // default password
            'phone'            => $validated['phone'] ?? null,
            'vehicle_type'     => $validated['vehicle_type'] ?? null,
            'vehicle_number'   => $validated['vehicle_number'] ?? null,
            'status'           => $validated['status'] ?? 'available',
            'total_deliveries' => 0,
            'max_orders'       => $validated['max_orders'] ?? 5,
        ]);

        return redirect()->route('drivers')->with('success', 'Driver added successfully.');
    }

    /**
     * Update the specified driver.
     */
    public function update(Request $request, Driver $driver)
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['nullable', 'email', 'max:255'],
            'phone'          => ['nullable', 'string', 'max:50'],
            'vehicle_type'   => ['nullable', 'string', 'max:50'],
            'vehicle_number' => ['nullable', 'string', 'max:50'],
            'status'         => ['nullable', 'in:available,on_delivery,offline'],
            'max_orders'     => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if (!isset($validated['max_orders'])) {
            $validated['max_orders'] = 5;
        }

        if (!isset($validated['status'])) {
            $validated['status'] = $driver->status;
        }

        $driver->update($validated);
        
        \App\Events\DriverStatusUpdated::safeDispatch($driver);

        return redirect()->route('drivers')->with('success', 'Driver updated successfully.');
    }

    /**
     * Update the status of the specified driver.
     */
    public function updateStatus(Request $request, Driver $driver)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:available,on_delivery,offline'],
        ]);

        $driver->update($validated);
        
        \App\Events\DriverStatusUpdated::safeDispatch($driver);

        return redirect()->route('drivers')->with('success', 'Driver status updated.');
    }

    /**
     * Remove the specified driver.
     */
    public function destroy(Driver $driver)
    {
        $driver->delete();

        return redirect()->route('drivers')->with('success', 'Driver removed.');
    }
}
