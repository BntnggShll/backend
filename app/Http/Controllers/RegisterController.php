<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Events\Registered; // <-- 1. IMPORT EVENT 'Registered'

class RegisterController extends Controller
{
    public function register_customer(Request $request)
    {
        try {
            // Validasi input (tetap sama)
            $validator = Validator::make($request->all(), [
                'name'     => 'required|string|max:255',
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

            // Simpan ke database (tetap sama)
            $user = User::create([
                'name'     => $request->name,
                'alamat'   => $request->alamat,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'     => 'customer',
            ]);

            event(new Registered($user));

            return response()->json([
                'success' => true,
                // 3. UBAH PESAN AGAR LEBIH JELAS
                'message' => 'Registrasi customer berhasil. Silakan cek email Anda untuk verifikasi.',
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
    public function resendVerificationEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Email tidak ditemukan.'], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Email tidak ditemukan.'], 404);
        }
        if ($user->hasVerifiedEmail()) {
            return response()->json(['success' => false, 'message' => 'Email ini sudah diverifikasi.'], 400);
        }

        // Kirim ulang notifikasi verifikasi
        $user->sendEmailVerificationNotification();

        return response()->json(['success' => true, 'message' => 'Link verifikasi baru telah dikirim ke email Anda.']);
    }
}