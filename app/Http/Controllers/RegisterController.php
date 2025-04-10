<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log; 

class RegisterController extends Controller
{
    public function register_customer(Request $request)
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
                'role'     => 'customer', // langsung set sebagai sales
            ]);

            return response()->json([
                'success' => true,
                'message' => 'customer registered successfully',
                'data'    => $user
            ], 201);

        } catch (\Exception $e) {
            // Log error untuk debugging
            Log::error('Register customer Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mendaftarkan customer',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    public function register_reseller(Request $request)
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
                'role'     => 'reseller', // langsung set sebagai sales
            ]);

            return response()->json([
                'success' => true,
                'message' => 'reseller registered successfully',
                'data'    => $user
            ], 201);

        } catch (\Exception $e) {
            // Log error untuk debugging
            Log::error('Register reseller Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mendaftarkan reseller',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    public function register_sales(Request $request)
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
                'role'     => 'sales', // langsung set sebagai sales
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sales registered successfully',
                'data'    => $user
            ], 201);

        } catch (\Exception $e) {
            // Log error untuk debugging
            Log::error('Register Sales Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mendaftarkan sales',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
