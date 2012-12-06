<?php

define('_PS_ADMIN_DIR_', getcwd());
define('PS_ADMIN_DIR', _PS_ADMIN_DIR_); // Retro-compatibility

include(PS_ADMIN_DIR . '/../../config/config.inc.php');
include(PS_ADMIN_DIR . '/../functions.php');
include(PS_ADMIN_DIR . '/../init.php');


/* Adding a new product image */
function addProductImage($product, $method = 'auto')
{
	$_errors = '';
	if (isset($_FILES['file']['tmp_name']) AND $_FILES['file']['tmp_name'] != NULL) {
		if (!Validate::isLoadedObject($product))
			$_errors = 'cannot add image because product add failed';
		else {
			// Create Image in memory
			$image = new Image();
			$image->id_product = (int)$product->id;
			//$_POST['id_product'] = $image->id_product;
			$image->position = Image::getHighestPosition($product->id) + 1;

			//---------------------------------------------------------------------------------
			/* Update product image/legend */

			//If no images present make this the cover image
			if (Image::getImagesTotal($product->id) > 0)
				$image->cover = false;
			else
				$image->cover = true;

			// Add Caption to each image
			$languages = Language::getLanguages();
			foreach ($languages as $language)
				$image->legend[$language['id_lang']] = Tools::getValue('legend_' . $language['id_lang']);

			//---------------------------------------------------------------------------------

			// If no errors, if image->add not set return error msg else copy image
			if ($_errors == '') {
				if (!$image->add())
					$_errors = 'error while creating additional image';
				else
					$_errors = copyImage($product->id, $image->id, $method);
			}
		}

		$id_image = $image->id;

	}

	if (isset($image) AND Validate::isLoadedObject($image) AND !file_exists(_PS_PROD_IMG_DIR_ . $image->getExistingImgPath() . '.' . $image->image_format))
		$image->delete();
	@unlink(_PS_TMP_IMG_DIR_ . '/product_' . $product->id . '.jpg');
	@unlink(_PS_TMP_IMG_DIR_ . '/product_mini_' . $product->id . '.jpg');

	return $_errors;

}

/* Copy a product image */

// @param integer $id_product Product Id for product image filename
// @param integer $id_image Image Id for product image filename

function copyImage($id_product, $id_image, $method = 'auto')
{
	if (!isset($_FILES['file']['tmp_name']))
		return false;

	$image = new Image($id_image);

	if (!$new_path = $image->getPathForCreation())
		$_errors[] = Tools::displayError('An error occurred during new folder creation');
	if (!$tmpName = tempnam(_PS_TMP_IMG_DIR_, 'PS') OR !move_uploaded_file($_FILES['file']['tmp_name'], $tmpName))
		$_errors[] = Tools::displayError('An error occurred during the image upload');
	elseif (!imageResize($tmpName, $new_path . '.' . $image->image_format))
		$_errors[] = Tools::displayError('An error occurred while copying image.'); elseif ($method == 'auto') {
		$imagesTypes = ImageType::getImagesTypes('products');
		foreach ($imagesTypes AS $k => $imageType)
			if (!imageResize($tmpName, $new_path . '-' . stripslashes($imageType['name']) . '.' . $image->image_format, $imageType['width'], $imageType['height'], $image->image_format))
				$_errors[] = Tools::displayError('An error occurred while copying image:') . ' ' . stripslashes($imageType['name']);
	}

	@unlink($tmpName);
	Module::hookExec('watermark', array('id_image' => $id_image, 'id_product' => $id_product));
}

// The Uploading Code
$result = array();

if (isset($_FILES['file'])) {
	$filename = $_FILES['file']['name'];
	$file = $_FILES['file']['tmp_name'];
	$error = false;
	$size = false;

// File Checking Control

	/*	Is Filesize small enough - This is now controlled by PlupLoader - also some of this is in images.inc.php

		//*$maxImageSize = 20000000;
		//if (!is_uploaded_file($file) || ($_FILES['file']['size'] > $maxImageSize ) )

		if (!is_uploaded_file($file) || ($_FILES['file']['size'] > 2 * 1024 * 1024) )
		{
			//$error = 'Please upload only files smaller than 2Mb';
		}
	 */

	// Is file an Image
	if (!$error && !($size = @getimagesize($file))) {
		$error = 'Please upload only images, no other files are supported.';
	}

	// Is file JPG, GIF, PNG 
	if (!$error && !in_array($size[2], array(1, 2, 3, 7, 8))) {
		$error = 'Please upload only images of type JPG, GIF, PNG.';
	}

// Error Handling and Action Section

	if ($error) {
		$result['result'] = 'failed';
		$result['error'] = $error;
	} else {

		if (Tools::getValue('type') == 'cms')
		{
			$result['result'] = 'error';
			$result['error'] = 'Not work yet';
			echo json_encode($result);
		} else {
			$check = 0;

			$product = new Product(Tools::getValue('id_product'));

			if (($info = addProductImage($product)) != '')
				$check = 1;

			if ($check == 1) {
				$result['result'] = 'failed';
				$result['error'] = $info;
			} else {
				$result['result'] = 'success';
				$result['size'] = "Uploaded an image success.";
			}
		}

	}

} else {
	$result['result'] = 'error';
	$result['error'] = 'Missing file or internal error!';

}

// Send JSON Header and Variables
if (!headers_sent()) {
	header('Content-type: application/json');
}

echo json_encode($result);

?>