<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
     // admin change passowrd page
    public function changePasswordPage(){
        return view('admin.account.changePassword');
    }

    // admin change passowrd
    public function changePassword(Request $request){
        $this->passwordValidationCheck($request);
        $user = User::select('password')->where('id',Auth::user()->id)->first();
        $dbHashValue = $user->password; //hash value

        if( Hash::check($request->oldPassword, $dbHashValue)) {

            $data =  ['password' => Hash::make($request->newPassword)];
            User::where('id',Auth::user()->id)->update($data);

            // Auth::logout();
            // return redirect()->route('auth#loginPage')->with(['changeSuccess' => 'Password Changed Success...']);

            return back()->with(['changeSuccess' => 'Password Change Success...']);
        }

        return back()->with(['notMatch' => 'The old password not match.Try again!']);
    }

    //direct admin details page
    public function detail(){
        return view('admin.account.detail');
    }

    // direct edit page
    public function editPage(){
        return view('admin.account.editPage');
    }

    //update profile
    public function update($id,Request $request){
        // dd($id,$request->all());
        $data = $this->accountValidationCheck($request);
        $data = $this->getUserData($request);

        //for image
        if($request->hasFile('image')){
            // 1.old image name 2.check=>dele 3.store

            $dbImage = User::where('id',$id)->first();
            $dbImage = $dbImage->image;

            if($dbImage != null){
               Storage::delete('public/'.$dbImage);
            }

            $fileName = uniqid() . $request->file('image')->getClientOriginalName();
            $request->file('image')->storeAs('public',$fileName);
            $data['image'] = $fileName ;

        }

        User::where('id',$id)->update($data);
        return redirect()->route('admin#detail')-> with(['updateSuccess' => 'Admin Account Updated . . .']);
    }

    //admin list
    public function list(){
        $admin = User::when(request('key'),function($query){
            $query -> orWhere('name','like','%'. request('key') .'%')
                   -> orWhere('email','like','%'. request('key') .'%')
                   -> orWhere('gender','like','%'. request('key') .'%')
                   -> orWhere('phone','like','%'. request('key') .'%')
                   -> orWhere('address','like','%'. request('key') .'%');
             })
             ->where('role','admin')->paginate(3);
        $admin -> appends(request()->all());
        return view('admin.account.list',compact('admin'));
    }
    //delete acc
    public function delete($id){
        User::where('id',$id )->delete();
        return back()->with(['deleteSuccess' => 'Admin Account Delete . . .']);
    }

    //  ajax role change status
    public function ajaxRoleChangeStatus(Request $request){
        User::where('id',$request->roleId)->update(['role' => $request->status]);
    }

    //request user data
    private function getUserData($request){
        return  [
             'name' => $request->name,
             'email' => $request->email,
             'phone' => $request->phone,
             'gender'=> $request->gender,
             'address' => $request->address,
             'updated_at' => Carbon::now(),
        ];
    }

    //Acc Validation Check
    private function accountValidationCheck($request){
        Validator::make($request->all(),
            [
                'name' => 'required',
                'email' => 'required',
                'phone' => 'required',
                'gender'=> 'required',
                'image' => 'required|mimes:jpeg,png,jpg,webp,file',
                'address' => 'required'
            ]
            )->validate();
    }


    //admin passwork validation check
    private function passwordValidationCheck($request){
        Validator::make($request->all(),[
          'oldPassword' => 'required|min:6|max:10',
          'newPassword' => 'required|min:6|max:10',
          'confirmPassword' => 'required|min:6|max:10|same:newPassword'
        ])->validate();
    }
}
