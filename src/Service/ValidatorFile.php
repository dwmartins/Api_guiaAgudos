<?php 

namespace App\Service;

class ValidatorFile {
    private $maxSizeImg = 5 * 1024 * 1024; // 5 MB
    private $extensionsImg = ['image/jpeg', 'image/jpg', 'image/png'];

    public function validImage($file) {
        $fileFormatInfo = $this->validImgFormat($file);
        $fileSizeInfo = $this->validImgSize($file);
        
        if(isset($fileFormatInfo['invalid'])) {
            return $fileFormatInfo;
        }

        if(isset($fileSizeInfo['invalid'])) {
            return $fileSizeInfo;
        }

        return true;
    }

    private function validImgSize($file) {
        $fileSize = $file->getSize();

        if($fileSize > $this->maxSizeImg){
            return ['invalid' => 'O arquivo excede o tamanho máximo permitido (5MB).'];
        }
    }

    private function validImgFormat($file) {
        $fileType = $file->getClientMimeType();
        
        if(!in_array($fileType, $this->extensionsImg)) {
            return ['invalid' => 'O formato da imagem deve ser JPEG, JPG ou PNG.'];
        }
    }
}