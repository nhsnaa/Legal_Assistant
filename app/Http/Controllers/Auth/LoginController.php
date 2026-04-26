namespace App\Http\Controllers\Auth; 

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Kreait\Laravel\Firebase\Facades\Firebase;

class LoginController extends Controller
{
    public function show()
    {
        return view('login');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $database = Firebase::database();
        // Get all users and filter by email
        $users = $database->getReference('users')
                 ->orderByChild('email')
                 ->equalTo($request->email)
                 ->getValue();

        if ($users) {
            // Firebase returns an array of matches; take the first one
            $firebaseUser = reset($users); 

            if (Hash::check($request->password, $firebaseUser['password'])) {
                // Manually set session data since we aren't using standard SQL Auth
                session(['user_email' => $firebaseUser['email']]);
                session(['user_role' => $firebaseUser['user_role']]);

                return redirect()->intended('/dashboard');
            }
        }

        throw ValidationException::withMessages([
            'email' => ['The provided credentials do not match our records.'],
        ]);
    }
}
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Kreait\Laravel\Firebase\Facades\Firebase;

class LoginController extends Controller
{
    public function show()
    {
        return view('login');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $database = Firebase::database();
        // Get all users and filter by email
        $users = $database->getReference('users')
                 ->orderByChild('email')
                 ->equalTo($request->email)
                 ->getValue();

        if ($users) {
            // Firebase returns an array of matches; take the first one
            $firebaseUser = reset($users); 

            if (Hash::check($request->password, $firebaseUser['password'])) {
                // Manually set session data since we aren't using standard SQL Auth
                session(['user_email' => $firebaseUser['email']]);
                session(['user_role' => $firebaseUser['user_role']]);

                return redirect()->intended('/dashboard');
            }
        }

        throw ValidationException::withMessages([
            'email' => ['The provided credentials do not match our records.'],
        ]);
    }
}