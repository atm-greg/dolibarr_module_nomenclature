<?php

require 'config.php';
dol_include_once('/product/class/product.class.php');
dol_include_once('/fourn/class/fournisseur.product.class.php');
dol_include_once('/core/class/html.formother.class.php');
dol_include_once('/core/lib/product.lib.php');
dol_include_once('/nomenclature/class/nomenclature.class.php');
dol_include_once('/product/class/html.formproduct.class.php');
if($conf->workstation->enabled) {
    dol_include_once('/workstation/class/workstation.class.php');
}
    


llxHeader('','Nomenclature');

$product = new Product($db);
$product->fetch(GETPOST('fk_product'), GETPOST('ref'));

$action= GETPOST('action');

$PDOdb=new TPDOdb;

if($action==='add_nomenclature') {
    
    $n=new TNomenclature;
    $n->set_values($_REQUEST);
    $n->save($PDOdb);
    
    
}
else if($action === 'delete_nomenclature_detail') {
    
    $n=new TNomenclature;
    $n->load($PDOdb, GETPOST('fk_nomenclature'));
    
    $n->TNomenclatureDet[GETPOST('k')]->to_delete = true;
    
    $n->save($PDOdb);
    
}
else if($action==='save_nomenclature') {
    
    $n=new TNomenclature;
    $n->load($PDOdb, GETPOST('fk_nomenclature'));
    $n->set_values($_POST);
    
    $n->is_default = (int)GETPOST('is_default');
    
	if($n->is_default>0) TNomenclature::resetDefaultNomenclature($PDOdb, $n->fk_product);
	
    if(!empty($_POST['TNomenclature'])) {
        foreach($_POST['TNomenclature'] as $k=>$TDetValues) {
            
            $n->TNomenclatureDet[$k]->set_values($TDetValues);
                    
        }
        
        
    }
    
    if(!empty($_POST['TNomenclatureWorkstation'])) {
        foreach($_POST['TNomenclatureWorkstation'] as $k=>$TDetValues) {
            
            $n->TNomenclatureWorkstation[$k]->set_values($TDetValues);
                    
        }
        
        
    }
    
    $fk_new_product = (int)GETPOST('fk_new_product');
    if(GETPOST('add_nomenclature') && $fk_new_product>0) {
        
        $k = $n->addChild($PDOdb, 'TNomenclatureDet');
        
        $det = &$n->TNomenclatureDet[$k];
        
        $det->fk_product = $fk_new_product;
        
    }
    
    $fk_new_workstation = GETPOST('fk_new_workstation');
    if(GETPOST('add_workstation') && $fk_new_workstation>0) {
        
        $k = $n->addChild($PDOdb, 'TNomenclatureWorkstation');
        
        $det = &$n->TNomenclatureWorkstation[$k];
        
        $det->fk_workstation = $fk_new_workstation;
        $det->rang = $k+1; 
    }
    
    
    $n->save($PDOdb);    
}


$head=product_prepare_head($product, $user);
$titre=$langs->trans('Nomenclature');
$picto=($product->type==1?'service':'product');
dol_fiche_head($head, 'nomenclature', $titre, 0, $picto);

headerProduct($product);

$form=new Form($db);

echo '<script type="text/javascript">
	function uncheckOther(obj)
	{
		$("input[name=is_default]").not($(obj)).prop("checked", false);	
	}
</script>';

$TNomenclature = TNomenclature::get($PDOdb, $product->id);

foreach($TNomenclature as &$n) {

    $formCore=new TFormCore('auto', 'form_nom_'.$n->getId(), 'post', false);
    echo $formCore->hidden('action', 'save_nomenclature');
    echo $formCore->hidden('fk_nomenclature', $n->getId());
    echo $formCore->hidden('fk_product', $product->id);
    
    ?>
    <table class="liste" width="100%">
        <tr class="liste_titre">
            <td class="liste_titre"><?php echo $langs->trans('Nomenclature').' n°'.$n->getId(); ?></td>
            <td class="liste_titre"><?php echo $formCore->texte($langs->trans('Title'), 'title', $n->title, 50,255); ?></td>
            <td class="liste_titre"><?php echo $formCore->texte($langs->trans('nomenclatureQtyReference'), 'qty_reference', $n->qty_reference, 5,10); ?></td>
            <td align="right" class="liste_titre"><?php echo $formCore->checkbox('', 'is_default', array(1 => $langs->trans('nomenclatureIsDefault')), $n->is_default, 'onclick="javascript:uncheckOther(this);"') ?></td>
        </tr>
        <tr>
           <td colspan="4">
               <?php
               
               if(count($n->TNomenclatureDet>0)) {
                   
                   ?>
                   <table width="100%" class="liste">
                       <tr class="liste_titre">
                           <td class="liste_titre"><?php echo $langs->trans('Type'); ?></td>
                           <td class="liste_titre"><?php echo $langs->trans('Product'); ?></td>
                           <td class="liste_titre"><?php echo $langs->trans('Qty'); ?></td>
                           <td class="liste_titre">&nbsp;</td>
                           <td class="liste_titre" align="right"><?php echo $langs->trans('AmountCost'); ?></td>
                       </tr>
                       <?php
                       $class='';$total_produit = $total_mo  = 0;
                       foreach($n->TNomenclatureDet as $k=>&$det) {
                           
                           $class = ($class == 'impair') ? 'pair' : 'impair';
                           
                           ?>
                           <tr class="<?php echo $class ?>">
                               <td><?php echo $formCore->combo('', 'TNomenclature['.$k.'][product_type]', TNomenclatureDet::$TType, $det->product_type) ?></td>
                               <td><?php 
                                    $p_nomdet = new Product($db);
                                    $p_nomdet->fetch($det->fk_product);
                                    
                                    echo $p_nomdet->getNomUrl(1).' '.$p_nomdet->label;
                                    
                               ?></td>    
                               <td><?php echo $formCore->texte('', 'TNomenclature['.$k.'][qty]', $det->qty, 7,100) ?></td>
                               
                               
                               <td><a href="?action=delete_nomenclature_detail&k=<?php echo $k ?>&fk_nomenclature=<?php echo $n->getId() ?>&fk_product=<?php echo $product->id ?>"><?php echo img_delete() ?></a></td>
                               <td align="right"><?php 
                                    $price = $det->getSupplierPrice($PDOdb, $det->qty); 
                                    $total_produit+=$price;
                                    echo price($price) ;
                                ?></td>                         
                           </tr>
                           <?
                           
                       }

                       ?>
                       <tr class="liste_total">
                           <td ><?php echo $langs->trans('Total'); ?></td>
                           <td colspan="3">&nbsp;</td>
                           <td align="right"><?php echo price($total_produit); ?></td>
                          
                       </tr>
                   </table>
                   
                   <?php
                   
               }
               
               ?>
           </td> 
            
        </tr>
        <?php
       if($conf->workstation->enabled) {
           
       ?><tr>
           <td colspan="4"><?php
               ?>
               <table class="liste" width="100%">
               <tr class="liste_titre">
                   <td class="liste_titre"><?php echo $langs->trans('Worstations'); ?></td>
                   <td class="liste_titre"><?php echo $langs->trans('QtyPrepare'); ?></td>
                   <td class="liste_titre"><?php echo $langs->trans('QtyFabrication'); ?></td>
                   <td class="liste_titre"><?php echo $langs->trans('Qty'); ?></td>
                   <td class="liste_titre"><?php echo $langs->trans('Rank'); ?></td>
                   <td class="liste_titre">&nbsp;</td>
                 <td class="liste_titre" align="right"><?php echo $langs->trans('AmountCost'); ?></td>
              
               </tr>
               <?php
                       
               if(!empty($n->TNomenclatureWorkstation)) {
                  
                   foreach($n->TNomenclatureWorkstation as $k=>&$ws) {
                       
                       $class = ($class == 'impair') ? 'pair' : 'impair';
                       
                       ?>
                       <tr class="<?php echo $class ?>">
                           <td><?php 
                                
                                echo $ws->workstation->getNomUrl(1);
                                
                           ?></td>    
                           <td><?php echo $formCore->texte('', 'TNomenclatureWorkstation['.$k.'][nb_hour_prepare]', $ws->nb_hour_prepare, 7,100) ?></td>
                           <td><?php echo $formCore->texte('', 'TNomenclatureWorkstation['.$k.'][nb_hour_manufacture]', $ws->nb_hour_manufacture, 7,100) ?></td>
                           <td><?php echo $ws->nb_hour ?></td>
                           <td><?php echo $formCore->texte('', 'TNomenclatureWorkstation['.$k.'][rang]', $ws->rang, 3,3) ?></td>
                           
                           <td><a href="?action=delete_ws&k=<?php echo $k ?>&fk_nomenclature=<?php echo $n->getId() ?>&fk_product=<?php echo $product->id ?>"><?php echo img_delete() ?></a></td>
                           <td align="right"><?php 
                                $price = $ws->workstation->thm * $ws->nb_hour; 
                                $total_mo+=$price;
                                echo price($price) ;
                           ?></td>                         
                       </tr>
                       <?
                       
                       
                   }

                    ?><tr class="liste_total">
                           <td><?php echo $langs->trans('Total'); ?></td>
                           <td colspan="4">&nbsp;</td>
                           <td>&nbsp;</td>
                           <td align="right"><?php echo price($total_mo); ?></td>
                          
                    </tr><?php
                   
               }
               else{
                        
                   echo '<tr><td colspan="5">'. $langs->trans('WillUseProductWorkstationIfNotSpecified') .'</td></tr>';
               }     
           
                ?>          
               </table><?php
                            
                            
            ?></td>
        </tr><?php
        }  
        ?>     
        <tr class="liste_total" >
                       <td style="font-weight: bolder;"><?php echo $langs->trans('Total'); ?></td>
                       <td colspan="2">&nbsp;</td>
                       <td style="font-weight: bolder; text-align: right;"><?php echo price($total_mo+$total_produit); ?></td>
                     
                </tr>  
        <tr>
            <td align="right" colspan="4">
                <div class="tabsAction">
                    <?php
                    
                    if($conf->workstation->enabled) {
                           
                           echo $formCore->combo('', 'fk_new_workstation', TWorkstation::getWorstations($PDOdb), -1);
                        ?>
                        <div class="inline-block divButAction">                        <input type="submit" name="add_workstation" class="butAction" value="<?php echo $langs->trans('AddWorkstation'); ?>" />
                        </div>
                        <?
                    }
                    
                    ?>
                    
                    <?php
                        print $form->select_produits('', 'fk_new_product', '', 0);
                    ?>
                   <div class="inline-block divButAction">
                    <input type="submit" name="add_nomenclature" class="butAction" value="<?php echo $langs->trans('AddProductNomenclature'); ?>" />
                   </div>
                   <div class="inline-block divButAction">
                    <input type="submit" name="save_nomenclature" class="butAction" value="<?php echo $langs->trans('SaveNomenclature'); ?>" />
                   </div>
                </div>
            </td>
        </tr>
    </table>
    <?php
    
    $formCore->end();
    
}


?>
<div class="tabsAction">
<div class="inline-block divButAction"><a href="?action=add_nomenclature&fk_product=<?php echo $product->id ?>" class="butAction"><?php echo $langs->trans('AddNomenclature'); ?></a></div>
</div>
<?php

dol_fiche_end();

  

llxFooter();
$db->close();


function headerProduct(&$object) {
   global $langs, $conf, $db; 
    
    $form = new Form($db);
        
    print '<table class="border" width="100%">';
    
    
    // Ref
    print '<tr>';
    print '<td width="15%">' . $langs->trans("Ref") . '</td><td colspan="2">';
    print $form->showrefnav($object, 'ref', '', 1, 'ref');
    print '</td>';
    print '</tr>';
    
    // Label
    print '<tr><td>' . $langs->trans("Label") . '</td><td>' . $object->libelle . '</td>';
    
    $isphoto = $object->is_photo_available($conf->product->multidir_output [$object->entity]);
    
    $nblignes = 5;
    if ($isphoto) {
        // Photo
        print '<td valign="middle" align="center" width="30%" rowspan="' . $nblignes . '">';
        print $object->show_photos($conf->product->multidir_output [$object->entity], 1, 1, 0, 0, 0, 80);
        print '</td>';
    }
    
    print '</tr>';
    
    
    // Status (to sell)
    print '<tr><td>' . $langs->trans("Status") . ' (' . $langs->trans("Sell") . ')</td><td>';
    print $object->getLibStatut(2, 0);
    print '</td></tr>';
    
    print "</table>\n";
    
  echo '<br />';
        
   
       
        
    
}
