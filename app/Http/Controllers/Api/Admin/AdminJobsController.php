<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminJobsController extends Controller
{
    public function index()
    {
        // Lấy danh sách công việc với thông tin liên quan
        $jobs = Job::with(['employer', 'locations', 'industries', 'jtype', 'jlevel'])->get();

        // Tùy chỉnh dữ liệu trả về
        $data = $jobs->map(function ($job) {
            return [
                'id' => $job->id,
                'title' => $job->jname,
                'employer' => [
                    'id' => $job->employer->id ?? null,
                    'name' => $job->employer->name ?? null,
                ],
                'locations' => $job->locations->map(function ($location) {
                    return [
                        'id' => $location->id,
                        'name' => $location->name,
                    ];
                }),
                'created_at' => $job->created_at ? $job->created_at->format('Y-m-d H:i:s') : null,
            ];
        });
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Danh sách công việc.',
            'status_code' => 200,
        ]);
    }

    public function show($id)
    {
        // Tìm công việc dựa trên ID
        $jobData = Job::with(['employer', 'locations', 'industries', 'jtype', 'jlevel'])->find($id);

        // Kiểm tra xem công việc có tồn tại không
        if (!$jobData) { // Use $jobData instead of $job
            return response()->json([
                'success' => false,
                'message' => 'Công việc không tồn tại.',
                'status_code' => 404,
            ], 404);
        }

        // Tùy chỉnh dữ liệu trả về
        $data = [
            'id' => $jobData->id,
            'title' => $jobData->title, // Giả sử bạn có thuộc tính title
            'description' => $jobData->description, // Mô tả công việc
            'employer' => [ // Thông tin nhà tuyển dụng
                'id' => $jobData->employer->id ?? null,
                'name' => $jobData->employer->name ?? null,
            ],
            'locations' => $jobData->locations->map(function ($location) {
                return [
                    'id' => $location->id,
                    'name' => $location->name,
                ];
            }),
            'industries' => $jobData->industries->map(function ($industry) {
                return [
                    'id' => $industry->id,
                    'name' => $industry->name,
                ];
            }),
            'jtype' => [ // Thông tin loại công việc
                'id' => $jobData->jtype->id ?? null,
                'name' => $jobData->jtype->name ?? null,
            ],
            'jlevel' => [ // Thông tin cấp bậc công việc
                'id' => $jobData->jlevel->id ?? null,
                'name' => $jobData->jlevel->name ?? null,
            ],
            'created_at' => $jobData->created_at ? $jobData->created_at->format('Y-m-d H:i:s') : null,
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Thông tin công việc.',
            'status_code' => 200,
        ]);
    }

    public function create(Request $request)
    {
        // Define the validation rules
        $validator = Validator::make($request->all(), [
            'jname' => 'required|string|max:150',
            'industries' => 'required|array',
            'industries.*' => 'integer|', // Ensure industry IDs exist in the industries table
            'jtype_id' => 'required|integer|', // Ensure jtype_id exists in job_types table
            'jlevel_id' => 'required|integer|', // Ensure jlevel_id exists in job_levels table
            'locations' => 'required|array',
            'locations.*' => 'integer|', // Ensure location IDs exist in the locations table
            'address' => 'required|string',
            'amount' => 'required|integer|min:1', // Ensure amount is a positive integer
            'yoe' => 'required|integer|min:0', // Ensure years of experience is a non-negative integer
            'expire_at' => 'required|date|after:today', // Ensure the expiration date is a valid date and in the future
            'description' => 'required|string',
        ], [
            'jname.required' => 'Tên công việc là bắt buộc.',
            'jname.string' => 'Tên công việc phải là chuỗi.',
            'jname.max' => 'Tên công việc không được vượt quá 150 ký tự.',
            'industries.required' => 'Ngành nghề là bắt buộc.',
            'industries.array' => 'Ngành nghề phải là một mảng.',
            'industries.*.integer' => 'Ngành nghề phải là số nguyên.',
            'industries.*.exists' => 'Ngành nghề không tồn tại.',
            'jtype_id.required' => 'Loại công việc là bắt buộc.',
            'jtype_id.integer' => 'Loại công việc phải là số nguyên.',
            'jtype_id.exists' => 'Loại công việc không tồn tại.',
            'jlevel_id.required' => 'Cấp độ công việc là bắt buộc.',
            'jlevel_id.integer' => 'Cấp độ công việc phải là số nguyên.',
            'jlevel_id.exists' => 'Cấp độ công việc không tồn tại.',
            'locations.required' => 'Vị trí làm việc là bắt buộc.',
            'locations.array' => 'Vị trí làm việc phải là một mảng.',
            'locations.*.integer' => 'Vị trí làm việc phải là số nguyên.',
            'locations.*.exists' => 'Vị trí làm việc không tồn tại.',
            'address.required' => 'Địa chỉ là bắt buộc.',
            'address.string' => 'Địa chỉ phải là chuỗi.',
            'amount.required' => 'Số lượng là bắt buộc.',
            'amount.integer' => 'Số lượng phải là số nguyên.',
            'amount.min' => 'Số lượng phải lớn hơn hoặc bằng 1.',
            'yoe.required' => 'Số năm kinh nghiệm là bắt buộc.',
            'yoe.integer' => 'Số năm kinh nghiệm phải là số nguyên.',
            'yoe.min' => 'Số năm kinh nghiệm không được nhỏ hơn 0.',
            'expire_at.required' => 'Ngày hết hạn là bắt buộc.',
            'expire_at.date' => 'Ngày hết hạn phải là một ngày hợp lệ.',
            'expire_at.after' => 'Ngày hết hạn phải sau hôm nay.',
            'description.required' => 'Mô tả là bắt buộc.',
            'description.string' => 'Mô tả phải là chuỗi.',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422); // HTTP 422 Unprocessable Entity
        }

        // Get all validated data
        $new_record = $validator->validated();

        // Extract industries and locations
        $industries = $new_record['industries'];
        unset($new_record['industries']);
        $locations = $new_record['locations'];
        unset($new_record['locations']);

        // Set the employer_id from the authenticated user
        $new_record['employer_id'] = $request->employer_id;

        // Create the job record
        $job = Job::create($new_record);

        // Insert job industries
        $job_industries = [];
        foreach ($industries as $industryId) {
            $job_industries[] = [
                'job_id' => $job->id,
                'industry_id' => $industryId,
            ];
        }
        DB::table('job_industry')->insert($job_industries);

        // Insert job locations
        $job_locations = [];
        foreach ($locations as $locationId) {
            $job_locations[] = [
                'job_id' => $job->id,
                'location_id' => $locationId,
            ];
        }
        DB::table('job_location')->insert($job_locations);

        // Prepare the response data
        $responseData = [
            'success' => true,
            'message' => 'Công việc đã được tạo thành công.',
            'data' => [
                'job' => [
                    'id' => $job->id,
                    'title' => $job->title,
                    'address' => $job->address,
                    'amount' => $job->amount,
                    'min_salary' => $job->min_salary,
                    'max_salary' => $job->max_salary,
                    'yoe' => $job->yoe,
                    'gender' => $job->gender,
                    'description' => $job->description,
                    'expire_at' => $job->expire_at,
                    'is_hot' => $job->is_hot,
                    'is_active' => $job->is_active,
                    'created_at' => $job->created_at->format('Y-m-d H:i:s'),
                    'industries' => $job_industries,
                    'locations' => $job_locations,
                ],
            ],
        ];

        return response()->json($responseData, 201); // HTTP 201 Created
    }





}
