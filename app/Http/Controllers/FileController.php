<?php

namespace App\Http\Controllers;

use Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use \Exception;

class FileController extends Controller
{   
    public function deleteS3File($s3Name){
        try {
            if (Storage::disk('s3')->exists($s3Name)){
                Storage::disk('s3')->delete($s3Name);
            }
        }
        catch (Exception $ex) {
            throw $ex;
        }

        return true;
    }

    public function fileUpload(Request $request, $input, $filePath){
        try {
            $file = $request->file($input);
            $fileName = $file->getClientOriginalName();
            $s3Name = $filePath .'/'. $fileName;

            // Remove any existing file if it exists
            $this->deleteS3File($s3Name);

            $request->file($input)->storeAs($filePath, $fileName, ['disk' => 's3', 'visibility' => 'public']);

            return response()->json([
                's3Name' => $s3Name
            ], 200);
        } 
        catch (Exception $ex) {
           return response()->json([
                'message' => $ex->getMessage()
            ], 400); 
        }
    }

    public function imageUpload(Request $request){
        return $this->fileUpload($request, 'image_file', 'images');
    }

    public function kmlUpload(Request $request){
        return $this->fileUpload($request, 'kml_file', 'kml');
    }

    public function kmlDelete(Request $request){
        $mapId = $request->input('map_id');
        $s3Name = $request->input('filename');
        $s3Status = 'unknown';
        $dbStatus = 'unknown';

        try {
            if ($this->deleteS3File($s3Name)){
                $s3Status = $s3Name .' removed from s3';
            }

            if (\App\Map::deleteFilenameFromDb($mapId, $s3Name)){
                $dbStatus = $s3Name .' removed from database';
            }
        } 
        catch (Exception $ex) {
           return response()->json([
                'message' => $ex->getMessage()
            ], 400); 
        }

        return response()->json([
            's3Status' => $s3Status,
            'dbStatus' => $dbStatus
        ], 200);
    }
}
