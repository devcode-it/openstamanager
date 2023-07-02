<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Jackiedo\DotenvEditor\DotenvEditor;
use PDOException;
use RuntimeException;
use function in_array;

class SetupController extends Controller
{
    public function __construct(private readonly DotenvEditor $dotenvEditor)
    {
    }

    /**
     * Test the database connection with the request data.
     */
    final public function testDatabase(Request $request): Response|JsonResponse
    {
        try {
            $request->validate([
                'database_driver' => 'required|string',
                'database_host' => 'required|string',
                'database_username' => 'required|string',
                'database_password' => 'nullable|string',
                'database_name' => 'required|string',
                'database_port' => 'required|integer',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }
        $database_name = $request->input('database_name');

        // Configure test connection
        config(['database.connections.testing' => [
            'driver' => $request->input('database_driver', 'mysql'),
            'host' => $request->input('database_host'),
            'port' => $request->input('database_port', '3306'),
            'password' => $request->input('database_password'),
            'database' => $database_name,
            'username' => $request->input('database_username'),
        ]]);

        $connection = DB::connection('testing');
        try {
            // Check DB connection either by checking if we can get PDO object or DB name
            $connection->getPdo();
            if (empty($connection->getDatabaseName())) {
                throw new RuntimeException(__('Database non trovato'));
            }
        } catch (PDOException|RuntimeException $e) {
            return response()->json([
                'message' => __('Impossibile connettersi al database: :message', ['message' => $e->getMessage()]),
                Response::HTTP_BAD_REQUEST,
            ]);
        }

        // Identifying permissions granted to the user
        $database_name = Str::replace('_', '\_', $database_name);

        $grants = $connection->select('SHOW GRANTS FOR CURRENT_USER');
        $requirements = [
            'SELECT',
            'INSERT',
            'UPDATE',
            'CREATE',
            'ALTER',
            'DROP',
        ];

        foreach ($grants as $result) {
            $privileges = current($result);

            if (Str::contains($privileges, [" ON `$database_name`.*", ' ON *.*'])) {
                $pieces = explode(', ', explode(' ON ', str_replace('GRANT ', '', (string) $privileges), 2)[0]);

                // Database-generic permissions
                if (in_array('ALL', $pieces) || in_array('ALL PRIVILEGES', $pieces)) {
                    $requirements = [];
                    break;
                }

                // Database-specific permissions
                foreach ($requirements as $key => $value) {
                    if (in_array($value, $pieces)) {
                        unset($requirements[$key]);
                    }
                }
            }
        }

        if ($requirements === []) {
            return response()->noContent();
        }

        return response()->json([
            'message' => __("L'utente del database non ha i seguenti permessi necessari: :permissions_list",
                ['permissions_list' => implode(', ', $requirements)]
            ),
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * Save config.
     */
    public function save(Request $request): JsonResponse|Response
    {
        // Check if the database connection is valid
        try {
            DB::connection()->getPdo();
        } catch (PDOException) {
            $response = $this->testDatabase($request);
            if ($response->getStatusCode() !== Response::HTTP_NO_CONTENT) {
                return $response;
            }
        }

        $chmod_result = File::chmod(base_path('.env'), 0644);
        try {
            $this->dotenvEditor->setKeys([
                'DB_CONNECTION' => $request->input('database_driver', 'mysql'),
                'DB_HOST' => $request->input('database_host'),
                'DB_PORT' => $request->input('database_port'),
                'DB_DATABASE' => $request->input('database_name'),
                'DB_USERNAME' => $request->input('database_username'),
                'DB_PASSWORD' => $request->input('database_password'),
            ])->save();
        } catch (Exception $e) {
            return response()->json([
                'message' => __('Impossibile scrivere il file di configurazione. :action', [
                    'action' => $chmod_result ? $e->getMessage() : 'Controllare i permessi del file .env',
                ]),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Cache config
        Artisan::call('cache:clear');
        Artisan::call('config:cache');

        // Run migrations
        Artisan::call('migrate', ['--force' => true]);

        try {
            $request->validate([
                'date_format_short' => 'required|string',
                'date_format_long' => 'required|string',
                'time_format' => 'required|string',
                'locale' => 'sometimes|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        settings($request->only(['date_format_short', 'date_format_long', 'time_format']));
        settings('locale', $request->input('locale', app()->getLocale()));

        return $this->saveAdmin($request);
    }

    public function saveAdmin(Request $request): Response|JsonResponse
    {
        try {
            $request->validate([
                'admin_username' => 'required|string|min:3|max:255|unique:users,username',
                'admin_password' => 'required|string|min:8|max:255',
                'admin_password_confirmation' => 'required|string|min:8|max:255|same:admin_password',
                'admin_email' => 'required|string|email|max:255|unique:users,email',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = new User();
        $user->username = $request->input('username');
        $user->email = $request->input('email');
        $user->password = Hash::make($request->input('password'));
        $user->save();

        return response()->noContent();
    }
}
