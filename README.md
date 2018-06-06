# tinymce_file_upload_plugin
File Upload plugin for TinyMCE

First of all you need to create a Space at https://spaces.mgniers.com/ (It is currently free).
After you create it, you will receive the credentials to use the API.

The installation process is:
1. Create your Space at https://spaces.mgniers.com/;
2. Copy the fileupload (plugin/fileupload) directory into your tinyMCE's plugin directory;
3. Open the file fileupload/config.php and change the value of the variables according to the parameters of your Space and your environment;
4. If you're going to use the PHP function mspaces_images, be sure to also enter the API key of your Space;
Done

It's required:
1. jQuery 2.x
2. TinyMCE 4.x
3. PHP 7.x

In the example, it includes some third-party software which can be provided under different forms of licensing:
1. jQuery: https://github.com/jquery/jquery
2. TinyMCE: https://www.tinymce.com
3. Lightbox: https://github.com/lokesh/lightbox2/