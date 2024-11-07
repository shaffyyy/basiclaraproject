<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Service; // Add the Service model
use App\Models\Window;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    // Index method to redirect users based on their user type
    public function index()
    {
        if (Auth::check()) {
            $usertype = Auth::user()->usertype;

            switch ($usertype) {
                case '0':
                    return view('user.home');
                case '1':
                    return view('admin.home');
                case '2':
                    return view('cashier.home.home');
                    // return route('cashier-index');
                case '3':
                    return view('fdcashier.home.home');
                default:
                    return redirect()->back()->with('error', 'Invalid user type.');
            }
        } else {
            return redirect()->route('login');
        }
    }

    //  * accounts related methods

    // Manage Users view
    public function manageUserView(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('usertype')) {
            $query->where('usertype', $request->usertype);
        }

        // Paginate the users with 10 users per page
        $users = $query->paginate(10);

        return view('admin.accounts.manage-user', compact('users'));
    }




    // Create a new user
    public function createUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'usertype' => 'required|integer|in:0,1,2,3',
        ]);

        $user = new User();
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->password = Hash::make($request->input('password'));
        $user->usertype = $request->input('usertype');
        $user->save();

        return redirect()->route('admin-manage-user')->with('success', 'User created successfully!');
    }

    public function editUser($id)
    {
        $user = User::findOrFail($id);
        return view('admin.accounts.edit-user', compact('user'));
    }

    public function updateUser(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'usertype' => 'required|integer|in:0,1,2,3',
        ]);

        $user = User::findOrFail($id);
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'usertype' => $request->usertype,
        ]);

        return redirect()->route('admin-manage-user')->with('success', 'User updated successfully!');
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('admin-manage-user')->with('success', 'User deleted successfully!');
    }









    // * Queue-related methods :
    // view queue
    public function viewQueue(Request $request)
    {
        // Get the verification filter from the request, defaulting to 'all'
        $verificationStatus = $request->input('verify', 'all');

        // Query the tickets based on the verification filter
        $queues = Ticket::with(['user', 'service', 'window']);

        if ($verificationStatus === 'verified') {
            $queues->where('verify', 'verified');
        } elseif ($verificationStatus === 'unverified') {
            $queues->where('verify', 'unverified');
        }

        // Get the filtered queues
        $queues = $queues->get();

        return view('admin.queue.queue-index', compact('queues', 'verificationStatus'));
    }


    // * windows-related methods :
    public function windowsView()
    {
        $windows = Window::with('service')->get(); // Fetch windows with related service
        return view('admin.windows.windows', compact('windows'));
    }
    
    public function addWindows()
    {
        $services = Service::all(); // Get all services for the dropdown
        $cashiers = User::where('usertype', '2')->get(); // Get all cashiers for the dropdown
        return view('admin.windows.add-windows', compact('services', 'cashiers'));
    }


    public function storeWindow(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'service_id' => 'required|exists:services,id',
            'cashier_id' => 'nullable|exists:users,id', // Validation for cashier assignment
        ]);

        Window::create([
            'name' => $request->name,
            'service_id' => $request->service_id,
            'cashier_id' => $request->cashier_id, // Store cashier assignment
        ]);

        return redirect()->route('admin-windows')->with('success', 'New window added successfully!');
    }


    public function deleteWindow($id)
    {
        $window = Window::findOrFail($id);
        $window->delete();

        return redirect()->route('admin-windows')->with('success', 'Window deleted successfully!');
    }

    public function editWindow($id)
    {
        $window = Window::findOrFail($id);
        $services = Service::all(); // Get all services for the dropdown
        $cashiers = User::where('usertype', '2')->get(); // Get all cashiers for the dropdown
        return view('admin.windows.edit-windows', compact('window', 'services', 'cashiers'));
    }



    // update the windows
    public function updateWindow(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'service_id' => 'required|exists:services,id',
            'cashier_id' => 'nullable|exists:users,id', // Validation for cashier assignment
        ]);

        $window = Window::findOrFail($id);
        $window->update([
            'name' => $request->name,
            'service_id' => $request->service_id,
            'cashier_id' => $request->cashier_id, // Update cashier assignment
        ]);

        return redirect()->route('admin-windows')->with('success', 'Window updated successfully!');
    }

    








    // * Service-related methods:
    //  View all services
    public function viewServices()
    {
        $services = Service::all();
        return view('admin.services.service-index', compact('services'));
    }

    // Show form to create a new service
    public function createService()
    {
        $services = Service::all();
        return view('admin.services.service-create', compact('services'));
    }

    // Store the newly created service in the database
    public function storeService(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
    
        Service::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);
    
        return redirect()->route('admin-view-services')->with('success', 'Service created successfully!');
    }
    

    // Edit a service
    public function editService($id)
    {
        $service = Service::findOrFail($id);
        return view('admin.services.service-edit', compact('service'));
    }


    // Update a service
    public function updateService(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|boolean',
        ]);

        $service = Service::findOrFail($id);
        $service->update([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
        ]);

        return redirect()->route('admin-view-services')->with('success', 'Service updated successfully!');
    }


    // Delete a service
    public function deleteService($id)
    {
        $service = Service::findOrFail($id);
        $service->delete();

        return redirect()->route('admin-view-services')->with('success', 'Service deleted successfully!');
    }
}
