<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class WebUserController extends ExtController
{
    public function index(){
        $users = User::query()
                ->get()
                ->toArray();
        return $this->send_success($users);
    }
    
    public function show($id = 0){
        $users = User::query()
                ->where("id", "=", $id)
                ->get()
                ->toArray()[0];
        return $this->send_success($users);
    }

    public function search(Request $request){
 
        $search = $request->q;

        // mengambil data dari table pegawai sesuai pencarian data
        $users = DB::table('users')
        ->select('id', 'name', 'telephone', 'school_id', 'created_at', 'updated_at')
        ->where('name','like',"%".$search."%")
        ->get()
        ->toArray();

            // mengirim data pegawai ke view index
        return $this->send_success(@json_decode(json_encode($users), true));
    }
    
    public function register(Request $request){

        
        $fields = $request->validate([
            'name' => 'required|string',
            'telephone' => 'required|integer',
            'image' => ['image', 'mimes:jpg', 'dimensions:max_width=1000,max_height=`1000'],
            'password' => 'required',
            'school_id' => 'required',
        ]);

        $name = $fields["name"];
        $password = bcrypt($fields["password"]);
        //$token = sha1($password);
        $school_id = (int)$fields["school_id"];
        $telephone = $fields["telephone"];
            
        $img = $request->file('image');
        /*
        $name = filter_input(INPUT_POST, $request->name, FILTER_SANITIZE_SPECIAL_CHARS);
        $password = Hash::make(filter_input(INPUT_POST, $request->password, FILTER_SANITIZE_SPECIAL_CHARS));
        $token = sha1($password);
        */
        // $email = filter_input(INPUT_POST, $request->email, FILTER_SANITIZE_EMAIL);
        
        $destination = "uploads/profile_pic";

        $user = User::create([
            "name" => $name,
            "password" => $password,
            "school_id" => $school_id,
            "telephone" => $telephone,
        ]);

        // Login
        auth()->login($user);

        if ($img->move($destination, $name.".jpg")){
            return redirect("/kelasku");
        }
        else{
            return redirect()->back()->withErrors("Gagal mengunggah gambar");
        }
        
    }

    public function edit_profile(Request $request){
            $form_fields = $request->validate([
                'name' => 'required',
                'school_id' => 'required',
            ]);
            rename(public_path("/images/player_icons/".auth()->user()->name.".jpg"), public_path('/images/player_icons/'.$form_fields["name"]."jpg"));
            User::query()
                ->where("id", "=", auth()->id())
                ->update($form_fields);
            $request->session()->regenerate();
            return redirect("/profile");
    }

    public function edit_password(Request $request){
        $prior_form_fields = ['name' => auth()->user()->name,
                            'telephone' => auth()->user()->telephone,
                            'password' => $request->old_password];
        // dd($prior_form_fields['password']);
        // Hash password
        

        if(auth()->attempt($prior_form_fields)){
            
            $form_fields = $request->validate([
                'new_password' => 'required',
                'confirmation_password' => 'required'
            ]);
            if ($form_fields["new_password"] == $form_fields["confirmation_password"]){
                $form_fields['new_password'] = bcrypt($form_fields['new_password']);
                User::query()
                    ->where("id", "=", auth()->id())
                    ->update(["password" => $form_fields["new_password"]]);
                $request->session()->regenerate();
                return redirect("/profile");
            }
        }
        return redirect()->back()->withErrors("Edit gagal");
    }

    public function logout(Request $request){
        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect("/");
    }

    public function login(Request $request){
        $form_fields = $request->validate([
            'telephone' => 'required',
            'password' => 'required'
        ]);

        if(auth()->attempt($form_fields)){
            $request->session()->regenerate();

            return redirect("kelasku")->with("message", "Login berhasil");
        }

        return redirect()->back()->withErrors("Login gagal");
    }

    public function delete(Request $request){
        User::query()
        ->where("id", "=", auth()->id())
        ->take(1)
        ->delete();

        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect("/");
    }
}
