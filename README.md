# minishop2-Link-and-ProductLink-import

Importing Links and ProductLinks from csv, like a product import.

Import scripts must be in dir: MODX_CORE_PATH/components/minishop2/import/

Do not forget to set remoteuser and remotehost vars in run.sh.

File csv-custom.php have modified gallery imort for multiple images. there are may be a comma-separated string with image names or one image.

                if ($v == 'gallery') {
                        if(preg_match('/,/',$csv[$k])){
                                $arrDataField = explode(',',$csv[$k]);
                                foreach($arrDataField as $filed){
                                        $gallery[] = $filed;
                                }
                        }
                        else{
                        $gallery[] = $csv[$k];
                        }
                }
