<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Hash;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\log;
class UserController extends Controller
{
    public function index()
    {
        return User::all();
    }

    public function store(Request $request)
    {
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'name'     => 'required|string|max:255',
                'alamat'   => 'required|string',
                'email'    => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors'  => $validator->errors()
                ], 422);
            }

            // Simpan ke database
            $user = User::create([
                'name'     => $request->name,
                'alamat'   => $request->alamat,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'     => 'admin', // langsung set sebagai admin
            ]);

            return response()->json([
                'success' => true,
                'message' => 'admin registered successfully',
                'data'    => $user
            ], 201);

        } catch (\Exception $e) {
            // Log error untuk debugging
            Log::error('Register admin Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mendaftarkan admin',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        return User::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->update($request->all());
        return $user;
    }

    public function destroy($id)
    {
        User::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }
}
