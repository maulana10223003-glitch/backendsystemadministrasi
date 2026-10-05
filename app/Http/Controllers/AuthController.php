<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        if (isset($user->status) && $user->status !== 'Aktif') {
            throw ValidationException::withMessages([
                'email' => ['Akun ini sudah dinonaktifkan. Hubungi administrator.'],
            ]);
        }

        // Hapus token lama biar gak numpuk tiap login
        $user->tokens()->delete();

        // Token expired otomatis sesuai config/sanctum.php ('expiration' dalam menit)
        $expirationMinutes = config('sanctum.expiration');
        $expiresAt = $expirationMinutes ? now()->addMinutes($expirationMinutes) : null;

        $token = $user->createToken('auth-token', ['*'], $expiresAt)->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'nama' => $user->nama,
                'email' => $user->email,
            ],
            'token' => $token,
            'expires_at' => $expiresAt, // dikasih tau ke frontend, opsional dipakai buat auto-logout di UI
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout berhasil.']);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        return response()->json([
            'id' => $user->id,
            'nama' => $user->nama,
            'email' => $user->email,
        ]);
    }
}