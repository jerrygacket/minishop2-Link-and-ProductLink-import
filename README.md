# minishop2-Link-and-ProductLink-import

Importing Links and ProductLinks from csv, like a product import.

Links updated, if exists. ProductLinks removed, then created. ProductLinks must be updated too, but there is no need to do right now. Maybe later.

Import scripts must be in dir: MODX_CORE_PATH/components/minishop2/import/

Do not forget to set remoteuser and remotehost vars in run.sh.

File csv-custom.php have modified gallery imort for multiple images. There are may be a comma-separated string with image names or singe image name.

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

Structure of csv files:

- links.csv

    - headers: name,type,class_key,description

    - data: link1;many-to-many;msLink;Link Description

- productlink.csv

    - headers: link,goods

    - data: link1;article1,article2,article3,article4
