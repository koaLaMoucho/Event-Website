<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Event;
use App\Models\EventImage;




class FileController extends Controller
{
    static $default = 'default.jpg';
    static $diskName = 'MediaStorage';
    static $systemTypes = [
        'profile_image' => ['png', 'jpg', 'jpeg', 'gif'],
        'event_image' => ['png', 'jpg', 'jpeg', 'gif'],
    ];

    private static function getDefaultExtension(String $type) {
        return reset(self::$systemTypes[$type]);
    }

    private static function isValidExtension(String $type, String $extension) {
        $allowedExtensions = self::$systemTypes[$type];
        return in_array(strtolower($extension), $allowedExtensions);
    }

    private static function isValidType(String $type) {
        return array_key_exists($type, self::$systemTypes);
    }

    private static function defaultAsset(String $type) {
        return asset($type . '/' . self::$default);
    }

    private static function getFileName(String $type, int $id, String $extension = null) {

        $fileName = null;
        switch($type) {
            case 'profile_image':
                $fileName = User::find($id)->profile_image; 
                break;
            case 'event_image':
                $eventImage = EventImage::find($id);
                if ($eventImage) {
                    $fileName = $eventImage->image_path;
                }
                break;
            default:
                return null;
        }

        return $fileName;
    }

    function delete(String $type, int $id) {
        $existingFileName = self::getFileName($type, $id);
        $response = ['message' => '', 'error' => false]; 

        if ($existingFileName) {
            try {
                Storage::disk(self::$diskName)->delete($type . '/' . $existingFileName);

                switch($type) {
                    case 'profile_image':
                        User::find($id)->profile_image = null;
                        break;
                    case 'event_image':
                        $eventImage = EventImage::find($id);
                        if ($eventImage) {
                            $eventImage->delete();
                        }
                        break;
                }

                $response['message'] = 'File deleted successfully';
            } catch (\Exception $e) {
                $response['message'] = 'Error deleting file: ' . $e->getMessage();
                $response['error'] = true;
            }
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    function upload(Request $request) {

        if (!$request->hasFile('file')) {
            return redirect()->back()->with('error', 'Error: File not found');
        }

        if (!$this->isValidType($request->type)) {
            return redirect()->back()->with('error', 'Error: Unsupported upload type');
        }

        $file = $request->file('file');
        $type = $request->type;
        $extension = $file->extension();
        if (!$this->isValidExtension($type, $extension)) {
            return redirect()->back()->with('error', 'Error: Unsupported upload extension');
        }

        $fileName = $file->hashName();

        $error = null;
        switch($request->type) {
            case 'profile_image':
                $user = User::findOrFail($request->id);
                if ($user) {
                    $user->profile_image = $fileName;
                    $user->save();
                } else {
                    $error = "unknown user";
                }
                break;

            case 'event_image':
                $event = Event::findOrFail($request->id);
                if ($event) {
                    $event->images()->create([
                        'image_path' => $fileName,
                    ]);
                } else {
                    $error = "Unknown event";
                }
                break;

            default:
                redirect()->back()->with('error', 'Error: Unsupported upload object');
        }

        if ($error) {
            redirect()->back()->with('error', `Error: {$error}`);
        }

        $file->storeAs($type, $fileName, self::$diskName);
        return redirect()->back()->with('success', 'Success: upload completed!');
    }

    static function get(String $type, int $id) {

        if (!self::isValidType($type)) {
            return self::defaultAsset($type);
        }

        $fileName = self::getFileName($type, $id);
        if ($fileName) {
            return asset($type . '/' . $fileName);
        }

        return self::defaultAsset($type);
    }
}