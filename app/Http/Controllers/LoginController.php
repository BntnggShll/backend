<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Hash;
use Illuminate\Http\Request;
use Storage;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        // 1. Validasi input yang masuk
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 2. Cari user berdasarkan email
        $user = User::where('email', $request->email)->first();

        // 3. Cek apakah user ada dan passwordnya cocok
        if (!$user || !Hash::check($request->password, $user->password)) {
            // Jika tidak cocok, kirim pesan error
            return response()->json([
                'success' => false,
                'message' => 'Email atau password yang Anda masukkan salah.',
            ], 401); // 401 Unauthorized
        }

        // 4. (Penting) Cek apakah email user sudah diverifikasi
        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda belum diverifikasi. Silakan cek email Anda.',
            ], 403); // 403 Forbidden
        }

        // 5. Jika semua berhasil, buat API token baru untuk user
        // Token ini yang akan digunakan untuk mengakses rute-rute terproteksi
        $token = $user->createToken('auth_token')->plainTextToken;

        // 6. Kirim respons sukses beserta token dan data user
        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'alamat' => $user->alamat,
                'no_telp' => $user->no_telp,
                'image' => $user->image ? asset('storage/' . $user->image) : null,
            ],
        ], 200);
    }

    public function updateUser(Request $request)
    {
        $user = $request->user(); // dapatkan user dari token

        // Validasi
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'no_telp' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Update field teks
        $user->name = $validated['name'];
        $user->alamat = $validated['alamat'] ?? $user->alamat;
        $user->no_telp = $validated['no_telp'] ?? $user->no_telp;

        // Jika ada gambar baru
        if ($request->hasFile('image')) {
            // Hapus gambar lama jika ada
            if ($user->image && Storage::exists($user->image)) {
                Storage::delete($user->image);
            }

            // Simpan gambar baru
            $path = $request->file('image')->store('profile', 'public');
            $user->image = $path;
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'alamat' => $user->alamat,
                'no_telp' => $user->no_telp,
                'image' => $user->image ? asset('storage/' . $user->image) : null,
            ],
        ]);
    }
    public function logout(Request $request)
    {
        // Hapus token yang sedang digunakan untuk otentikasi
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Anda telah berhasil logout.',
        ], 200);
    }

    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }
}
