<?php
/**
 * Created by Md. Atiqur Rahman.
 * Email: atiq.cse.cu0506.su@gmail.com
 * Skype: atiq.cu
 * Date: 4/13/2015
 * Time: 10:06 PM
 */

require_once "common.php";

    if(isset($_FILES['brandLogo'])){

        $ups = $ftp->sendFromFile($_FILES['brandLogo'],false, false, 'brandLogo__');

        if($ups['result']===true){

            header('location: index.php?page=__list.brand&msg='.urlencode('A new Brand successfully created! '));
            exit();

        }else{
            echo 'File upload failed!! retry';
            $ftp->dump($ups);
        }

    }else die('form submission failed');



