<?php
    
    define('INC_FROM_CRON_SCRIPT',true);
    require('../config.php');

    $langs->load('nomenclature@nomenclature');

?>
var ButtonWhoSubmit;

function showLineNomenclature(fk_line, qty, fk_product, object_type, fk_origin) {

       var bindItem = function () {
           
           $div.find('[type="submit"]').click(function(e){
                    ButtonWhoSubmit = $(this).attr('name');
               });    
               
               $div.find('a').each(function(){
                    $a = $(this);
                    var url = $(this).attr('href');
                    $(this).attr('href','javascript:;');
                    $(this).unbind().click(function() {
                       $.ajax({
                           url: url
                       }).done(function(data) {
                           $a.closest('.ui-dialog').effect( "shake", { direction : 'up', times : 1 } );
                            $div.html(data);
                            bindItem();
                       });
                        
                    });
                    
               });    
               
               $div.find('form').submit(function() {
                   var data = $(this).serialize();
                   data+='&'+ButtonWhoSubmit+'=1';
                   /*console.log(data);*/
                   $.post($(this).attr('action'), data, function() {
                        $div.dialog('option','title',"<?php echo $langs->transnoentities('NomenclatureLineSaved'); ?>");
                   }).done(function(data) {
                       $div.closest('.ui-dialog').effect( "shake", { direction : 'up', times : 1 } );
                       $div.html(data);
                       bindItem();
                       
                       if (ButtonWhoSubmit == 'apply_nomenclature_price')
                       {
                       		var url = false;
                       		switch(object_type) {
							    case 'propal':
							        url = "<?php echo dol_buildpath('/comm/propal.php?id=', 2); ?>"+fk_origin;
							        break;
							    case 'commande':
							        url = "<?php echo dol_buildpath('/commande/card.php?id=', 2); ?>"+fk_origin;
							        break;
							} 
							
							if (url)
							{
								$.ajax({
									url: url
									,success: function(html) {
										$('#id-right > .fiche').replaceWith($(html).find('#id-right > .fiche'));
									}
								});
							}
							
                       }
                   });
            
                    return false;
               });
                
           
       }

       var openDialog = function(data) {
                
               $("#dialog-nomenclature").remove(); 
                
               $('body').append('<div id="dialog-nomenclature"></div>');
               
               $div = $("#dialog-nomenclature");
               $div.html(data);
               bindItem();
               
               $("#dialog-nomenclature").dialog({
                  resizable: true,
                  modal: true,
                  dialogClass: "dialogSouldBeZindexed",
                  width:'90%',
                  title:"<?php echo $langs->trans('Nomenclature'); ?>",
                  buttons: {
                    
                  }
                })
       };
       
/* nomenclature/nomenclature.php?fk_product=2&lineid=4&object_type=commande&qty=1 */
       $.ajax({
           url:"<?php echo dol_buildpath('/nomenclature/nomenclature.php',1); ?>"
           ,data: {
                 fk_object: fk_line
                , qty_ref: qty
                , fk_product: fk_product  
                , object_type: object_type
                , fk_origin: fk_origin
                , json : 1
           } 
           ,dataType:'html'
       }).done(function(data){
           
           openDialog(data);
           
       }) ;
       
}
