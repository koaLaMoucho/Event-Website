@extends('layouts.app')

@section('content')
<section id="profile">
    <i class="fa-regular fa-user" aria-label="User" ></i>

    <section>
        @csrf
 
        @if($user->profile_image != null)
            <img id="profile-image" src="{{ \App\Http\Controllers\FileController::get('profile_image', $user->user_id) }}" alt="Profile Image">
        @else
            <img id="profile-image" src="{{ asset('media/default_user.jpg') }}" alt="Default Profile Image">
        @endif
   
        <label for="name">Name:</label>
        <input type="text" id="edit_name" name="name" value="{{ $user->name }}" required disabled>
        @error('name')
            <span class="text-danger">{{ $message }}</span>
        @enderror

        <form method="POST" action="/file/upload" enctype="multipart/form-data">
            @csrf
            <input name="file" type="file" required>
            <input name="id" type="number" value="{{ $user->user_id }}" hidden>
            <input name="type" type="text" value="profile_image" hidden>
            <button type="submit" class="btn btn-outline-primary">Submit</button>
        </form>


        <label for="email">Email:</label>
        <input type="email" id="edit_email" name="email" value="{{ $user->email }}" required disabled>
        @error('email')
            <span class="text-danger">{{ $message }}</span>
        @enderror

       
        <label for="phone_number">Phone Number:</label>
        <input type="text" id="edit_phone_number" name="phone_number" value="{{ $user->phone_number }}" required disabled>
        @error('phone_number')
            <span class="text-danger">{{ $message }}</span>
        @enderror

        <br>
        <button type="button" class="btn btn-outline-primary" id="edit-profile-button" onclick="toggleProfileButtons()">Edit Profile</button>
        <button type="button" class="btn btn-outline-primary" id="update-profile-button" onclick="updateProfile()" style="display: none;">Save Changes</button>
        
    </section>
</section>

@endsection
