<?php

namespace App\Http\Controllers;

use App\Models\GeneralSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GeneralSettingController extends Controller
{
    public function create()
    {
        $data['page_title'] = 'Settings';
        // $data['setting']    = GeneralSetting::get();
        return view('generalsetting.create', $data);
    }

     public function store(Request $request)
    {
        if ($request->form_type == 'company-detail') {
            $request->validate([
                'company_logo'    => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'company_email'   => 'required|email',
                'company_phone'   => 'required',
                'company_address' => 'required',
                // 'gst'             => 'required',
            ]);
        } elseif ($request->form_type == 'general-setting') {
            $request->validate([
                'login_page_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'copyright_msg'    => 'required',
            ]);
        } else {
            //
        }

        $data = $request->except('_token');
        $data = $request->except('company_logo');
        $data = $request->except('login_page_image');

        if (!$data) {
            return redirect()->back()->with('error', 'Request data is empty.');
        } else {
            $data = $request->except(['_token', 'company_logo', 'login_page_image', 'form_type']);
            foreach ($data as $key => $value) {
                GeneralSetting::updateOrCreate(
                    ['key' => $key],         /* Search by key */
                    ['value' => $value]      /* Update or set value */
                );
            }

            if ($request->hasFile('login_page_image')) {
                // $general_setting = GeneralSetting::where('key', 'login_page_image')->first();
                $general_setting = GeneralSetting::firstOrNew(['key' => 'login_page_image']);
                /* Delete old profile picture if exists */
                if (isset($request->login_page_image) && $general_setting && $general_setting->login_page_image) {
                    Storage::disk('public')->delete('login_page_image/' . $general_setting->login_page_image);
                }
                /* Upload new profile picture */
                $file = $request->file('login_page_image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('login_page_image', $filename, 'public');  /* Save in storage/app/public/login_page_image */

                /* Save new filename in database */
                $general_setting->value = $filename ?? '';
                $general_setting->save();
            }

            if ($request->hasFile('company_logo')) {
                 $general_setting = GeneralSetting::firstOrNew(['key' => 'company_logo']);
                /* Delete old profile picture if exists */
                if (isset($request->company_logo) && $general_setting && $general_setting->company_logo) {
                    Storage::disk('public')->delete('company_logo/' . $general_setting->company_logo);
                }
                /* Upload new profile picture */
                $file = $request->file('company_logo');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('company_logo', $filename, 'public');  /* Save in storage/app/public/company_logo */

                /* Save new filename in database */
                $general_setting->value = $filename;
                $general_setting->save();
            }

            return redirect()->back()->withSuccess('Setting update successfully.');
        }
    }
}
