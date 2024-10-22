<?php

namespace App\Http\Controllers\Api\Employer;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use App\Utillities\Common;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EmployerController extends Controller
{
    public function logo(Request $request)
    {
        $user_id = auth()->user()->id;
        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|mimes:jpeg,jpg,png|max:1024',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 400);
        }

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $ext = $file->getClientOriginalExtension();
            $filename = time() . '.' . $ext;

            // Retrieve the old logo filename
            $oldLogo = Employer::where('users_id', $user_id)->value('logo');

            // Delete the old logo file
            if (is_file(public_path('images/' . $oldLogo))) {
                unlink(public_path('images/' . $oldLogo));
            }

            // Move the new file to the uploads directory
            $file->move(public_path('images/'), $filename);

            // Update the logo filename in the database
            Employer::where('users_id', $user_id)->update([
                'logo' => $filename
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Logo updated successfully.',
                'logo_filename' => asset('images/' . $filename) // Return full URL
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No logo file provided.',
            ], 400);
        }
    }




    public function image(Request $request)
    {
        $user_id = auth()->user()->id;

        $validator = Validator::make($request->all(), [
            'banner' => 'required|image|mimes:jpeg,jpg,png|max:1024',
        ], [
            'banner.required' => 'Banner là bắt buộc.',
            'banner.image' => 'Banner phải là hình ảnh.',
            'banner.mimes' => 'Banner phải là định dạng jpeg, jpg hoặc png.',
            'banner.max' => 'Banner không được vượt quá 1MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 400);
        }

        if ($request->hasFile('banner')) { // Chỉnh sửa từ 'logo' thành 'banner'
            $file = $request->file('banner');
            $ext = $file->getClientOriginalExtension();
            $filename = time() . '.' . $ext;

            // Retrieve the old banner filename
            $oldBanner = Employer::where('users_id', $user_id)->value('banner');

            // Delete the old banner file
            if (is_file(public_path('images/' . $oldBanner))) {
                unlink(public_path('images/' . $oldBanner));
            }

            // Move the new file to the uploads directory
            $file->move(public_path('images/'), $filename);

            // Update the banner filename in the database
            Employer::where('users_id', $user_id)->update([
                'banner' => $filename
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Banner updated successfully.',
                'banner_filename' => asset('images/' . $filename) // Return full URL
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No banner file provided.',
            ], 400);
        }
    }

    public function update(Request $request)
    {
        // Xác thực dữ liệu đầu vào
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'min_employees' => 'nullable|integer',
            'max_employees' => 'nullable|integer',
            'contact_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:15',
            'description' => 'nullable|string',
            'website' => 'nullable|url',
            'date_of_establishment' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 400);
        }

        $company = Employer::where('user_id', Auth::user()->id)->first();

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found.',
            ], 404);
        }

        $data = [];

        // Địa chỉ IP của server
        $serverIp = "http://101.101.96.43";  // Thay bằng địa chỉ IP của bạn

        // Upload logo nếu có
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $path = public_path('uploads/images');
            $file_name = Common::uploadFile($file, $path);
            $data['logo'] = $serverIp . '/uploads/images/' . $file_name;

            // Xóa logo cũ nếu có
            if ($company->logo) {
                $oldLogoPath = public_path('uploads/images/' . basename($company->logo));
                if (is_file($oldLogoPath)) {
                    unlink($oldLogoPath);
                }
            }
        }

        // Upload banner nếu có
        if ($request->hasFile('image')) {
            $bannerFile = $request->file('image');
            $path = public_path('uploads/images');
            $bannerFileName = Common::uploadFile($bannerFile, $path);
            $data['image'] = $serverIp . '/uploads/images/' . $bannerFileName;

            // Xóa banner cũ nếu có
            if ($company->image) {
                $oldBannerPath = public_path('uploads/images/' . basename($company->image));
                if (is_file($oldBannerPath)) {
                    unlink($oldBannerPath);
                }
            }
        }

        // Cập nhật thêm thông tin từ request vào mảng $data
        $data['name'] = $request->input('name');
        $data['address'] = $request->input('address');
        $data['min_employees'] = $request->input('min_employees');
        $data['max_employees'] = $request->input('max_employees');
        $data['contact_name'] = $request->input('contact_name');
        $data['phone'] = $request->input('phone');
        $data['description'] = $request->input('description');
        $data['website'] = $request->input('website');
        $data['founded_year'] = $request->input('founded_year');

        // Cập nhật thông tin công ty trong database
        $company->update($data);

        // Tùy chỉnh dữ liệu trả về
        $companyData = [
            'id' => $company->id,
            'name' => $company->name,
            'address' => $company->address,
            'min_employees' => $company->min_employees,
            'max_employees' => $company->max_employees,
            'logo' => $company->logo ? $serverIp . '/uploads/images/' . basename($company->logo) : null,
            'banner' => $company->image ? $serverIp . '/uploads/images/' . basename($company->image) : null,
            'contact_name' => $company->contact_name,
            'phone' => $company->phone,
            'description' => $company->description,
            'website' => $company->website,
            'founded_year' => $company->founded_year,
        ];

        return response()->json([
            'success' => true,
            'message' => "Cập nhật thành công.",
            'data' => $companyData,
            'status_code' => 200
        ]);
    }




}
