<?php
/**
 * Created by Md. Atiqur Rahman
 * Email: atiq.cse.cu0506.su@gmail.com
 * Skype: atiq.cu
 * Date: 4/23/2015
 * Time: 1:53 AM
 */
/*
ini_set('display_errors',1);
ini_set('E_ERROR',1);
error_reporting(-1);
*/


require_once 'cls.ftp.basic.php';

$user = 'atiq';
$pass='@secret';
$ftp = new FtpBasic($user, $pass);
$ftp->changeDestinationDir('/public_html/product_images/');

//$ftp->dump($db, false);
//$ftp->dump($ftp);