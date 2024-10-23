<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminCandidateController extends Controller
{
    // Liệt kê tất cả ứng viên
    public function index()
    {
        // Lấy danh sách ứng viên với role = 1 và kèm theo thông tin từ model Candidate
        $candidates = User::where('role', 1)->with('candidate')->get();

        // Tùy chỉnh dữ liệu trả về
        $data = $candidates->map(function ($candidate) {
            return [
                'user' => [ // Thông tin người dùng
                    'id' => $candidate->id,
                    'email' => $candidate->email,
                    'is_active' => $candidate->is_active, // Trạng thái tài khoản
                    'created_at' => $candidate->created_at ? $candidate->created_at->format('Y-m-d H:i:s') : null,
                ],
                'candidate' => [ // Thông tin ứng viên
                    'firstname' => $candidate->candidate->firstname ?? null,
                    'lastname' => $candidate->candidate->lastname ?? null,
                    'gender' => $candidate->candidate->gender ?? null,
                    'dob' => $candidate->candidate->dob ?? null,
                    'phone' => $candidate->candidate->phone ?? null,
                    'address' => $candidate->candidate->address ?? null,
                    'link' => $candidate->candidate->link ?? null,
                    'avatar' => $candidate->candidate->avatar ?? null,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Danh sách ứng viên.',
            'status_code' => 200,
        ]);
    }





    // Cập nhật thông tin ứng viên
    public function update(Request $request, $id)
    {
        // Validate incoming request data
        $validator = Validator::make($request->all(), [
            'firstname' => 'nullable|string|max:100',
            'lastname' => 'nullable|string|max:100',
            'password' => 'nullable|string|min:6', // Password can be empty
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validate the image
        ], [
            'email.email' => 'Email không đúng định dạng.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
            'image.image' => 'Ảnh không đúng định dạng.',
            'image.max' => 'Ảnh phải nhỏ hơn 2MB.',
        ]);

        // If validation fails, return error
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors(),
                'status_code' => 422,
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Find the user by ID
            $user = User::findOrFail($id);

            // Update user information
            $user->email = $request->email; // This should not be null

            // Only update password if provided
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save(); // Save user information

            // Update candidate information
            $candidate = Candidate::where('user_id', $user->id)->first();
            if ($candidate) {
                $candidate->firstname = $request->firstname;
                $candidate->lastname = $request->lastname;
                $candidate->gender = $request->gender;
                $candidate->dob = $request->dob;
                $candidate->phone = $request->phone;
                $candidate->email = $request->email;
                $candidate->address = $request->address;
                $candidate->objective = $request->objective;

                // Handle image upload
                $file = $request->file('image');
                if ($file) {
                    $extension = $file->getClientOriginalExtension();
                    $fname = 'avatar_candidate_' . '_' . $candidate->id . '.' . $extension;
                    $path =  'http://101.101.96.43/' . '/storage/' . $file->storeAs('avatar_images', $fname, 'public');
                    $candidate->avatar = $path;
                }
                if ($request->delete_img) {
                    $candidate->avatar = NULL;
                }

                $candidate->save(); // Save candidate information
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user, // Updated user information
                    'candidate' => $candidate, // Updated candidate information
                ],
                'message' => 'Tài khoản ứng viên đã được cập nhật thành công.',
                'status_code' => 200,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            // Log error if there is a problem
            Log::error('Cập nhật tài khoản ứng viên thất bại: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Cập nhật tài khoản ứng viên thất bại.',
                'status_code' => 500,
            ], 500);
        }
    }

    public function show($id)
    {
        // Tìm ứng viên dựa trên ID và role = 1
        $candidate = User::where('role', 1)->with('candidate')->find($id);

        // Kiểm tra xem ứng viên có tồn tại không
        if (!$candidate) {
            return response()->json([
                'success' => false,
                'message' => 'Ứng viên không tồn tại.',
                'status_code' => 404,
            ], 404);
        }

        // Tùy chỉnh dữ liệu trả về
        $data = [
            'user' => [ // Thông tin người dùng
                'id' => $candidate->id,
                'email' => $candidate->email,
                'is_active' => $candidate->is_active,
                'created_at' => $candidate->created_at ? $candidate->created_at->format('Y-m-d H:i:s') : null,
            ],
            'candidate' => [ // Thông tin ứng viên
                'firstname' => $candidate->candidate->firstname ?? null, // Lấy firstname từ candidate
                'lastname' => $candidate->candidate->lastname ?? null,
                'gender' => $candidate->candidate->gender ?? null,
                'dob' => $candidate->candidate->dob ?? null,
                'phone' => $candidate->candidate->phone ?? null,
                'address' => $candidate->candidate->address ?? null,
                'link' => $candidate->candidate->link ?? null,
                'avatar' => $candidate->candidate->avatar ?? null,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Thông tin ứng viên.',
            'status_code' => 200,
        ]);
    }



    // Xóa tài khoản ứng viên
    public function destroy($id)
    {
        // Find the user by ID
        $user = User::find($id);

        // Check if the user exists and has the correct role
        if (!$user || $user->role !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Ứng viên không tồn tại.',
                'status_code' => 404,
            ], 404);
        }

        // Find the candidate associated with the user
        $candidate = Candidate::where('user_id', $user->id)->first();

        // Delete the candidate data if it exists
        if ($candidate) {
            $candidate->delete(); // Delete the candidate record
        }

        // Delete the user
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tài khoản ứng viên đã được xóa thành công.',
            'status_code' => 200,
        ]);
    }

    public function store(Request $request)
    {
        // Xác thực dữ liệu đầu vào
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|max:100',
            'lastname' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
        ], [
            'firstname.required' => 'Tên là bắt buộc.',
            'lastname.required' => 'Họ là bắt buộc.',
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email đã tồn tại.',
            'password.required' => 'Mật khẩu là bắt buộc.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ]);

        // Nếu validation thất bại, trả về lỗi
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors(),
                'status_code' => 422,
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Tạo user mới với dữ liệu từ request
            $user = User::create([
                'email' => $request->email,
                'role' => 1,
                'is_active' => 1,
                'password' => Hash::make($request->password),
            ]);

            // Tạo candidate cho user
            $candidate = Candidate::create([
                'id' => $user->id, // Sử dụng ID của người dùng
                'user_id' => $user->id, // Liên kết với người dùng
                'firstname' => $request->firstname, // Họ
                'lastname' => $request->lastname,
                'email' => $request->email,
            ]);

            DB::commit();

            // Tùy chỉnh dữ liệu trả về
            $data = [
                'user' => [ // Thông tin người dùng
                    'id' => $user->id,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : null,
                ],
                'candidate' => [ // Thông tin ứng viên
                    'firstname' => $candidate->firstname,
                    'lastname' => $candidate->lastname,
                    'email' => $candidate->email,
                    'gender' => $candidate->gender ?? null,
                    'dob' => $candidate->dob ?? null,
                    'phone' => $candidate->phone ?? null,
                    'address' => $candidate->address ?? null,
                    'link' => $candidate->link ?? null,
                    'avatar' => $candidate->avatar ?? null,
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Tài khoản ứng viên đã được tạo thành công.',
                'status_code' => 201,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            // Log lỗi nếu có vấn đề
            Log::error('Tạo tài khoản ứng viên thất bại: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Tạo tài khoản ứng viên thất bại.',
                'status_code' => 500,
            ], 500);
        }
    }


}
