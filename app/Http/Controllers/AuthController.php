<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;


class AuthController extends Controller
{
/**
 * @OA\Post(
 *     path="/api/register",
 *     summary="Register User",
 *     tags={"Auth"},
 *     @OA\RequestBody(
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 @OA\Property(
 *                     property="name",
 *                     type="string",
 *                     example="foo"
 *                 ),
 *                 @OA\Property(
 *                     property="age",
 *                     type="number",
 *                     example=24
 *                 ),
 *                 @OA\Property(
 *                     property="email",
 *                     type="string",
 *                     format="email",
 *                     example="foo@gmail.com"
 *                 ),
 *                 @OA\Property(
 *                     property="password",
 *                     type="string",
 *                     example="123abc"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="OK",
 *         @OA\JsonContent(
 *             @OA\Property(property="name", type="string", example="foo"),
 *             @OA\Property(property="age", type="number", example=24),
 *             @OA\Property(property="email", type="string", format="email", example="foo@gmail.com"),
 *             @OA\Property(property="password", type="string", example="123abc")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Error",
 *     )
 * )
 */

    public function register(Request $request){

        $validator = Validator::make(request()->all(), [
            'name' => 'required',
            'email'=> 'required|string|email|max:255|unique:users',
            'password'=> 'required|string',
        ]);
        if ($validator->fails()){
            return response()->json($validator->errors());
        }
        $user = User::create([
            'name'=> $request->name,
            'email'=> $request->email,
            'password'=> Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'data' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
    /**
 * @OA\Post(
 *     path="/api/login",
 *      summary="Login User",
 *     tags={"Auth"},
 *     @OA\RequestBody(
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 
 *                 @OA\Property(
 *                     property="email",
 *                     type="string",
 *                     format="email",
 *                     example="foo@gmail.com"
 *                 ),
 *                 @OA\Property(
 *                     property="password",
 *                     type="string",
 *                     example="123abc"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="OK",
 *         
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Error",
 *     )
 * )
 */
    public function login(Request $request){
        if (!Auth::attempt($request->only('email','password'))) {
            return response()->json(['message' => 'Unautorized'],401);
        }
        $user = User::where('email', $request['email'])->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json(['message'=> 'Hi '.$user->name,
        'accessToken' => $token,
        'token_type'=> 'Bearer',
        'user' => $user,]);

    }
    /**
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Auth"},
     *      summary="Logout User",
     *     description="Invalidated the current access token",
     *     @OA\Response(response="200", description="OK"),
     *     @OA\Response(response="401", description="Unautorized"),
     *     security={
     *         {"sanctum": {}}
     *     }
     * )
     */
    public function logout(Request $request){
        $request->user()->tokens()->delete();
         return response()->json([
         'message' => 'Successfully logged out'
          ]);
    }
    

}
