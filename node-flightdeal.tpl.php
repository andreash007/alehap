<?php
// $Id: node.tpl.php,v 1.6 2011/02/18 05:47:53 andregriffin Exp $

//global $CARTLESS;
$CARTLESS = FALSE;
$CARTTEST = (variable_get('alehap_carttest', FALSE) == '1' && ($user->uid == 1 || array_search('developer', $user->roles) !== FALSE || array_search('uctest', $user->roles) !== FALSE || array_search('product manager', $user->roles) !== FALSE)) ? TRUE : FALSE;

//Get Flight Deal policy
if (substr_count($node->field_dealtype[0]['value'],'Flight')) {
	$flightnode = node_load($node->field_dealaux[0]['nid']);
	if ($flightnode->type == "flight_deal_fields") {
		$flight_policy = ($flightnode->field_flight_deal_policy[0]['value'] ? check_markup($flightnode->field_flight_deal_policy[0]['value'], ($flightnode->field_flight_deal_policy[0]['format'] === NULL ? FILTER_FORMAT_DEFAULT : $flightnode->field_flight_deal_policy[0]['format']), FALSE) : '');
	}
	//If there's deal auxiliary, but deal aux type is not flight, log this error.
	if ($flightnode && $flightnode->type != "flight_deal_fields") {
		watchdog('alehap','Invalid deal auxiliary type');
	}
}


$company_node =  node_load($node->field_company[0]['nid']);
$addr_node = node_load($company_node->field_address[0]['nid']);

// Could we use fieldgroup_groups?
$sql = "SELECT field.field_name, field.weight FROM `content_node_field_instance` as field inner join `content_group_fields` as groupf on (field.field_name = groupf.field_name) where groupf.group_name='group_deal_promotion_type' and field.type_name='deal' order by weight";
$result = db_query($sql);
while ($row = db_fetch_array($result)) {
	if ($node->{$row['field_name']}[0]['value'] == 1) {
		$promotion_type = $row['field_name'];
		break;
	}
}

$promotion_type_image_map = array(
	'field_deal_of_the_day'		=>	'deal-of-the-day',
	'field_deal_of_the_week'	=>	'deal-of-the-week',
	'field_hot_deal'			=>	'hot-deal',
	'field_red_hot_deal'		=>	'red-hot-deal',
	'field_alehap_favourite'	=>	'alehap-favourite',
);

$interval = strtotime($node->field_bookdates[0]['value2']) - time();
if ($interval > 0 && $node->field_dealstatus[0]['value'] != 'Past' ) {
	$interval = format_interval($interval,4);
	$arr = explode(" ", $interval);
	$interval_arr = array();
	for($i=0;$i<count($arr);$i+=2) {
		$interval_arr[$arr[$i+1]] = $arr[$i];
	}
}

if ($node->created < $template_separate_time) {
//FOR nodes that created before 03/03/2014, use old template
 ?>

 <div class="innr-contentblog1">
  <div class="innr-contentblog1left">
    <h1><?php print $title; ?></h1>
    <div class="subtitle"><h2><strong><?php print $node->field_subtitle[0]['value']; ?></strong></h2><span><?php print format_date($node->created,'custom',"d.m.Y"); ?></span></div>
    <div class="inner-slider">
    <?php
      	$img_url = $node->field_img_url[0]['value'];
		if($img_url != "") {
			if (file_exists($img_url)) {
				$ret = db_query("SELECT fid FROM {files} WHERE filepath='%s'",$img_url);
				$row = db_fetch_array($ret);
				$fid = $row['fid'];

				//Get data from field image cache
				$ret2 = db_query("SELECT field_image_cache_data FROM {content_field_image_cache} WHERE field_image_cache_fid = %d",$fid);
				$row2 = db_fetch_array($ret2);
				$data = unserialize($row2['field_image_cache_data']);
				$img_title = $data['title'];
				$alt = $data['alt'];
			}else{
				$img_url = "";
			}
		}
		if ($img_url == "" && $node->field_image_cache[0]['filepath']) {
			$img_url = $node->field_image_cache[0]['filepath'];
			$img_title = $node->field_image_cache[0]['data']['title'];
			$alt = $node->field_image_cache[0]['data']['alt'];
		}

		if ($img_url == "" && $node->field_image_cache[0]['filepath'] == "") {
			watchdog('alehap','missing image. Both file upload field and url text are left blank');
		}
	  ?>
	<?php if (0) { print theme('imagecache','Deal-Main-Image',$node->field_image_cache[0]['filepath']);} ?>
    <img src="<?php print base_path() . $img_url?>" width="607" title="<?php print $img_title ?>" alt="<?php print $alt ?>" />
    </div>
  </div>
  <div class="inner-sidecontent">
  <p class="inner-sidecontenttitle">
  	<?php if (!empty($promotion_type)) { ?>
	<img class="<?php print $promotion_type_image_map[$promotion_type] ?>" src="<?php print base_path() . path_to_theme() . "/images/hotdeal/" . $promotion_type_image_map[$promotion_type] ?>.png" />
	<?php } ?>
</p>
    <p class="sidelinks">
    <?php if (time() < strtotime($node->field_bookdates[0]['value'])) { ?>
    <a href="javascript:void()"><?php print t('Not yet available') ?></a>
    <?php }elseif ( time() >= strtotime($node->field_bookdates[0]['value']) && time() <= (strtotime($node->field_bookdates[0]['value2']+86399)) ) { ?>
    <a href="javascript:void()"><?php print t('Available') ?></a>
    <?php }elseif (time() > strtotime($node->field_bookdates[0]['value2']) || $node->field_dealstatus[0]['value'] == 'Past' ) { ?>
    <a href="javascript:void()" class="sidelink"><?php print t('Expired') ?></a>
    <?php } ?>
    </p>
    <p class="side-add">
		<?php if (isset($node->sell_price) && $node->sell_price != 0) { ?>
		<strong><?php print t('Best price') ?></strong>
        <?php
		//$discounts = get_codeless_discounts_for_product_and_quantity($node, 1);
		//$discounted_price = alehap2_get_discounted_price_for_product($node,$return_null = TRUE);
		if ($discounted_price!="") { ?>
        	<span class="old-price"><?php print number_format($node->sell_price,0,".","."); ?> VNĐ</span>
            <?php
            	$saving = ($node->sell_price - $discounted_price);
			?>
            <span class="saving"><?php print t('Saving')." ".number_format($saving,0,".",".")." VNĐ" ?></span>
            <span><?php print number_format($discounted_price,0,".","."); ?> VNĐ</span>
        <?php }else{ ?>
        	<span><?php print number_format($node->sell_price,0,".","."); ?> VNĐ</span>
        <?php } ?>
		<?php }else{ ?>
		<strong><?php print t('Call us for current price') ?></strong><span><?php $phone = variable_get('alehap_phone', '(08) 6264 5789'); print $phone ?></span>
		<?php } ?>
    </p>
    <p class="side-quote1">
      <?php 	$interval = ( strtotime($node->field_bookdates[0]['value2']) - time());
		if ($interval > 0 && $node->field_dealstatus[0]['value'] != 'Past' ) {
			print t('Time remaining') . ":&nbsp;" . format_interval($interval,7);
		}
	?>
    </p>

    <div class="date-pong1">
    <?php if ((!$CARTLESS || $CARTTEST) && $is_shoppable) { ?>
      <?php print $node->content['add_to_cart']['#value']; ?>
      <?php print $field_subproducts_rendered; ?>
 	<?php }else{ ?>
      <span class="call-us-text"><?php print t('Call us') ?><br /><?php $phone = variable_get('alehap_phone', '(08) 6264 5789'); print $phone ?></span>
    <?php } ?>
    </div>

  </div>
</div>
<div class="hotelblog">
  <div class="hotelblog-leftcontent">
    <div class="reviews-blog reviews-blog4">
      <h3 class="reviews-title reviews-title4"><span><?php print $title; ?></span></h3>
      <ul class="resorts-blog">
      <?php if (strpos($node->field_dealtype[0]['value'], 'Hotel') !== FALSE) { ?>
      <li><strong><?php print t('Location') ?></strong><span><?php
	  		//Company (maybe Hotel, Airline or travel service) is higher priority
			$place_node = node_load($addr_node->field_place[0]['nid']);
			if (!$place_node || strpos($node->field_dealtype[0]['value'], 'Tour') === TRUE || strpos($node->field_dealtype[0]['value'], 'Flight') === TRUE ) {
				//if we don't have place_node from address,or this is not a Hotel Deal, we use mainplace
				$place_node = node_load($node->field_mainplace[0]['nid']);
			}

			if (!$place_node) {
                watchdog('alehap', 'missing any place');
			}
			//Get all terms
			$parents = taxonomy_get_parents_all($place_node->field_placeterm[0]['value']);
			$places = "";
			$first = TRUE;
			foreach ($parents as $parent) {
				//Get place node by term ID -  related to "place_by_term" view
				$place_by_term = sqrl('place_by_term', $parent->tid);
				//Load correspoding term object
				$term_obj = taxonomy_get_term($place_by_term[0]['field_placeterm_value']);
				//Append to places string
				//If this is the first item of object, do not append the ','
				if ($first) {
					$places .= ($term_obj?$term_obj->name:'');
					$first = FALSE;
				}else{
					$places .= ($term_obj?', '.$term_obj->name:'');
				}
				//IF we reach "Country" place node, break loop
				if ($place_by_term[0]['field_placetype_value'] == "Country") {
					break;
				}
			}
			//if street address is available, append ',' to it.
			//if not, $street_address blank
			$street_address = ($addr_node->field_streetaddress[0]['value'])?check_plain($addr_node->field_streetaddress[0]['value']).', ':'';
			print $street_address . $places;
                  ?></span></li>
        <?php } ?>

        <?php if (strpos($node->field_dealtype[0]['value'], 'Hotel') !== FALSE || strpos($node->field_dealtype[0]['value'], 'Tour') !== FALSE) { ?>
        <li><strong><?php print t('Time') ?></strong><span><?php print $node->field_duration[0]['value']; ?></span></li>
        <?php } ?>

        <?php if (0) { ?><li><strong><?php print t('Type') ?></strong><span><?php print $node->field_dealtype[0]['value']; ?></span></li><?php } ?>
<?php if (strpos($node->field_dealtype[0]['value'], 'Flight') !== FALSE) { ?>
		<?php if ($company_node && $company_node->status == 1) { ?>
        <li><strong><?php print t('Airline') ?></strong><span><a href="<?php print base_path() . $company_node->path ?>" target="_blank"><?php print $company_node->title ?>&nbsp;(<?php print t('Detail'); ?>)</a></span></li>
        <?php } ?>
<?php } ?>
<?php /*        <li><strong><?php print t('Room') ?></strong><span>Deluxe Garden BungaLow</span></li> */ ?>
        <li><strong>
        <?php print t('For Travel -  Applies') ?>
        </strong><span><?php print format_date(strtotime($node->field_traveldates[0]['value']),"custom",'d.m.Y') . " - " . format_date(strtotime($node->field_traveldates[0]['value2']),'custom','d.m.Y') ?></span></li>

        <li><strong>
        <?php print t('Book Tickets - Expires') ?>
        </strong><span><?php print format_date(strtotime($node->field_bookdates[0]['value']),'custom','d.m.Y') . " - " . format_date(strtotime($node->field_bookdates[0]['value2']),'custom','d.m.Y'); ?></span></li>

      </ul>
      <h2 class="inner4title3"><?php  print t('Description'); ?></h2>
      <div class="hotelblog-leftcontentblog1 hotelblog-leftcontentbloginner4">
      <span class="leftcontentblog1text"> <?php print $node->content['body']['#value']; ?>
      </span>
      </div>

      <?php if ($flight_policy && strpos($node->field_dealtype[0]['value'], 'Flight')!==FALSE) { ?>
      <h2 class="inner4title3"><?php print t('Flight Deal Policy'); ?></h2>
      <div class="hotelblog-leftcontentblog1 hotelblog-leftcontentbloginner4">
      <span class="leftcontentblog1text"> <?php print $flight_policy; ?> </span>
      </div>
      <?php } ?>

    </div>
    <!-- Comment FlightDeails -->
	<div class="reviews-blog">
    <?php if ($node->field_alehap_review && !empty($node->field_alehap_review[0]['value'])) { ?>
		<h3 class="reviews-title"><span><?php print t('Alehap reviews') ?></span></h3>
		<div class="alehap-reviews">
			<?php print $node->field_alehap_review[0]['value'] ?>
		</div>
		<?php } ?>
    <div class="collapsible">
    <h3 class="reviews-title collapse-processed"><span><?php print t('User reviews') ?></span><a href="javascript:void()"></a></h3>
		<div class="collapsible">
		<?php
			if (function_exists('comment_render') && $node->comment) {
				$edit = array('nid' => $node->nid);
				//print comment_render($node) . comment_form_box($edit);
				print comment_render($node);
				$node->comment = NULL;
			}
		?>
		</div>
    </div>
    </div>

	<div class="g-plusone-wrapper">
    	<g:plusone href="https://alehap.vn/<?php print $node->path; ?>"></g:plusone>
    </div>
	<div class="fb-like-button">
    	<fb:share-button href="https://alehap.vn/<?php print $node->path ?>" type="button"></fb:share-button>
		<fb:like <?php print $fbml_param; ?> layout="button_count" send="false" width="100" show_faces="false"></fb:like>
        <fb:comments <?php print $fbml_param; ?> width="600" num_posts="10"></fb:comments>
	</div>

    <div class="innerbotom-blog innerbotom-blog2">
    <h3 class="reviews-title reviews-title4 reviews-title5"><?php print t('Similar Deals') ?></h3>
      <?php
				//load the view by name
				print views_embed_view('similar_deals', $display_id = 'default', $node->nid, $node->field_mainplace[0]['nid']);
				?>
    </div>
  </div>
  <div class="hotelblog-rightcontent">
    <?php if (($menu = menu_local_tasks())) { ?>
    <ul class="side-nav">
      <?php print $menu ?>
    </ul>
    <?php } ?>
<?php /*if (strpos($node->field_dealtype[0]['value'], 'Hotel') !== FALSE) { ?>
    <div class="rating-blog">
      <div class="rating-blogcontent">
        <div class="map3"><span class="maptitle"><?php print t('Map') ?></span><a href="#inline_content" class="inline showlink"><?php print t('Show map') ?></a>
          <div class="google-map">
            <!--<img src="<?php print base_path().path_to_theme() ?>/images/map.png" width="207" height="168" alt="" />-->
            <?php $gmap = gmap_location_block_view($node->nid); print $gmap['content'];?>
          </div>
        </div>
      </div>
      <p class="map3bg-bottom"></p>
    </div>
<?php }*/ ?>
<?php if (0) { ?>
    Rating not available
              <div class="rating-blog">
              <div class="rating-blogcontent">
              <strong>Rating Details</strong>
              <div class="rating-main">
              <div class="rating-maincontent">
                <ul class="rating-details">
                  <li><span>Staff</span><b>9.0</b></li>
                  <li><span>Food</span><b>9.0</b></li>
                  <li><span>Location</span><b>9.0</b></li>
                  <li><span>Service</span><b>9.0</b></li>
                  <li><span>Cleanliness</span><b>9.0</b></li>
                  <li><span>Staff</span><b>9.0</b></li>
                  <li><strong>TOTAL</strong><i>9.0</i></li>
                </ul>
                </div>
                <p class="ratingbottom"></p>
                </div>
                </div>
                <p class="map3bg-bottom"></p>
              </div>
<?php } ?>
  </div>
</div>
<!-- This contains the hidden content for inline calls-->
<!--
<div style="position:fixed; bottom:-1000px">
  <div id='inline_content'>
    <?php $gmap2 = gmap_location_block_view($node->nid); print $gmap2['content'];?>
  </div>
</div>-->

<?php }else{
//FOR nodes that created after 03/03/2014, use new template
?>

<div class="innr-contentblog1 hotelblog new">
  <div class="innr-contentblog1left add">
    <h1><?php print $title; ?></h1>
    <div class="subtitle add1"><h2><strong><?php print $node->field_subtitle[0]['value']; ?></strong></h2><span><?php print format_date($node->created,'custom',"d.m.Y"); ?></span></div>
    <div class="inner-slider add2">
    <?php
      	$img_url = $node->field_img_url[0]['value'];
		if($img_url != "") {
			if (file_exists($img_url)) {
				$ret = db_query("SELECT fid FROM {files} WHERE filepath='%s'",$img_url);
				$row = db_fetch_array($ret);
				$fid = $row['fid'];

				//Get data from field image cache
				$ret2 = db_query("SELECT field_image_cache_data FROM {content_field_image_cache} WHERE field_image_cache_fid = %d",$fid);
				$row2 = db_fetch_array($ret2);
				$data = unserialize($row2['field_image_cache_data']);
				$img_title = $data['title'];
				$alt = $data['alt'];
			}else{
				$img_url = "";
			}
		}
		if ($img_url == "" && $node->field_image_cache[0]['filepath']) {
			$img_url = $node->field_image_cache[0]['filepath'];
			$img_title = $node->field_image_cache[0]['data']['title'];
			$alt = $node->field_image_cache[0]['data']['alt'];
		}

		if ($img_url == "" && $node->field_image_cache[0]['filepath'] == "") {
			watchdog('alehap','missing image. Both file upload field and url text are left blank');
		}
	  ?>
	<?php if (0) { print theme('imagecache','Deal-Main-Image',$node->field_image_cache[0]['filepath']);} ?>
    <img src="<?php print base_path() . $img_url?>" width="607" title="<?php print $img_title ?>" alt="<?php print $alt ?>" />
    </div>
  </div>
  <div class="inner-sidecontent">
  <!--<p class="inner-sidecontenttitle">-->
  	<!-- test deal thu -->

    <div id="right">
	<div class="menu_left">
    	<div class="top"></div>
        <?php if (!empty($promotion_type)) { ?>
		<div class="<?php print $promotion_type_image_map[$promotion_type] ?>" ></div>
		<?php } ?>
        <div class="content">

            <div class="desc">
            	<div class="best"><?php print t('Best price') ?></div>
               <!-- <div class="price">PRICE FROM</div>--><br />
                <span class="cur"><?php print number_format($node->sell_price,0,".","."); ?> <span>đ</span></span>
                <!--<p>Original Price: <span class="color">$200</span> <br /> Save <span class="color">37%</span></p>-->
                <?php
                	if($node->field_inc_tax[0]['value'] == FALSE) {
						$tax_notice = t('Excluding taxes and fees');
					}else{
						$tax_notice = t('Including taxes and all fees');
					}
				?>
                <p class="ex-tax"><?php print $tax_notice; ?></p>
            </div>

            <div class="title">
            	<?php if (time() < strtotime($node->field_bookdates[0]['value'])) { ?>
                <a href="javascript:void()"><?php print t('Not yet available') ?></a>
                <?php }elseif (time() >= strtotime($node->field_bookdates[0]['value']) && time() <= (strtotime($node->field_bookdates[0]['value2']) + 86399) ) { ?>
                <a href="javascript:void()" class="available"><?php print t('Available') ?></a>
                <?php }elseif (time() > strtotime($node->field_bookdates[0]['value2']) || $node->field_dealstatus[0]['value'] == 'Past' ) { ?>
                <a href="javascript:void()"><span class="master"><?php print t('Expired') ?></span></a>
                <?php } ?>
            </div>

        </div>

        <div class="button">
			<?php if(count($interval_arr)>0) { ?>
			<?php foreach($interval_arr as $k => $v) { ?>
        	<div class="bnt_left"><?php print $v." ".$k ?></div>
			<?php } ?>
			<?php }else{ ?>
			<div class="bnt_left">0 week</div>
			<div class="bnt_left">0 day</div>
			<div class="bnt_left">0 minute</div>
			<div class="bnt_left">0 second</div>
			<?php } ?>
            <!--<div class="bnt_right">0 HOUR</div>
            <div class="bnt_left">9 MINS</div>
            <div class="bnt_right">23 SECS</div>-->
            <?php 	/*$interval = ( strtotime($node->field_bookdates[0]['value2']) - time());
				if ($interval > 0 && $node->field_dealstatus[0]['value'] != 'Past' ) {?>
				<div class="bnt_left"> <?php print t('Time remaining') . ":&nbsp;" . format_interval($interval,7); ?></div>
				<?php }*/
			?>
        </div>

        <!--<div class="book">
        	<div class="book_left">
        		<img src="<?php print base_path().path_to_theme() ?>/images/book.png" width="105" height="75" border="0" style="margin-top:5px" />
            </div>
            <div class="book_right">
            	<span class="mua"><img src="<?php print base_path().path_to_theme() ?>/images/check.png" width="26" height="24" border="0" /> 36<br /> <?php print t('bought'); ?></span>
            </div><div class="clear"></div>
        </div>-->

        <div class="info">
        	<fieldset>
        	<!--<form action="" method="post" enctype="multipart/form-data">
                <label>Number of adults</label> <br/>
                <select>
                    <option value="1">Dummy text 1</option>
                    <option value="2">Dummy text 2</option>
                    <option value="3">Dummy text 3</option>
                </select><br/>
                <label>Number of children</label><br/>
                <select>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                </select><br/>
                <label>Hotel Type</label><br/>
                <select>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                </select><br/>
                <label>Travel By</label><br/>
                <select>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                </select><br/>
                <label>Quantity: rooms tickets vouchers</label><br/>
                <input type="text" name="txt_quan" size="20px" />
                <br/>
                <label>Travel Date</label><br />
                <input type="text" name="txt_date" size="20px" />
                <p></p>

               <div class="books">
					<input type="submit" name="ok" value="BOOK TOUR" />
			   </div>
        	</form>-->
			<div class="clear"></div>
			<div class="add-to-cart_form_wrapper">
            <?php if ((!$CARTLESS || $CARTTEST) && $is_shoppable) { ?>
			<div class="book-available"><p class="line1"><?php print t('Available online'); ?></p><p class="line2"><?php print t('book this deal securely online') ?></p></div>
      			<?php print $node->content['add_to_cart']['#value']; ?>
      			<?php print $field_subproducts_rendered; ?>
 				<?php }else{ ?>
				<div class="book-offline"><p class="line1"><?php print t('Call/Chat'); ?></p><p class="line2"><?php print t('book by phone (08) 6264 5789 or chat') ?></p></div>
      			<!--<span class="call-us-text"><?php print t('Call us') ?><br /><?php $phone = variable_get('alehap_phone', '(08) 6264 5789'); print $phone ?></span>-->
    		<?php } ?>
			</div>
            </fieldset>
        </div>
    </div>

</div>

    <!-- ket thuc deal -->

		  <div class="hotelblog-rightcontent f">
    <?php if (($menu = menu_local_tasks())) { ?>
    <ul class="side-nav">
      <?php print $menu ?>
    </ul>
    <?php } ?>
<?php if (0) { ?>
    Rating not available
              <div class="rating-blog">
              <div class="rating-blogcontent">
              <strong>Rating Details</strong>
              <div class="rating-main">
              <div class="rating-maincontent">
                <ul class="rating-details">
                  <li><span>Staff</span><b>9.0</b></li>
                  <li><span>Food</span><b>9.0</b></li>
                  <li><span>Location</span><b>9.0</b></li>
                  <li><span>Service</span><b>9.0</b></li>
                  <li><span>Cleanliness</span><b>9.0</b></li>
                  <li><span>Staff</span><b>9.0</b></li>
                  <li><strong>TOTAL</strong><i>9.0</i></li>
                </ul>
                </div>
                <p class="ratingbottom"></p>
                </div>
                </div>
                <p class="map3bg-bottom"></p>
              </div>
<?php } ?>
  </div>

  </div>

	  <div class="hotelblog-leftcontent test">
    <div class="reviews-blog reviews-blog4 test1">
	<div class="frame-wrapper">
      <!---<h3 class="reviews-title reviews-title4 test2"><span><?php print $title; ?></span></h3>-->
      <!--them div ul-->
      <ul class="resorts-blog u">


		<?php if (strpos($node->field_dealtype[0]['value'], 'Flight') !== FALSE) { ?>
		<?php if ($company_node && $company_node->status == 1) { ?>
        <li><strong><?php print t('Airline') ?></strong><span><a href="<?php print base_path() . $company_node->path ?>" target="_blank"><?php print $company_node->title ?>&nbsp;(<?php print t('Detail'); ?>)</a></span></li>
        <?php } ?>
		<?php } ?>

        <li class="two-columns"><strong>
		<?php if (strpos($node->field_dealtype[0]['value'], 'Hotel') !== FALSE) { ?>
		<?php print t('Stay between - Applies') ?>
        <?php }elseif (strpos($node->field_dealtype[0]['value'], 'Flight') !== FALSE) { ?>
        <?php print t('For Travel -  Applies') ?>
        <?php }else{ ?>
        <?php print t('Trip Departs - Applies') ?>
        <?php } ?>

        </strong><span><?php print format_date(strtotime($node->field_traveldates[0]['value']),"custom",'d.m.Y') . " - " . format_date(strtotime($node->field_traveldates[0]['value2']),'custom','d.m.Y') ?></span>

		<strong>

		<?php if (strpos($node->field_dealtype[0]['value'], 'Hotel') !== FALSE) { ?>
		<?php print t('Book Before - Expires') ?>
        <?php }elseif (strpos($node->field_dealtype[0]['value'], 'Flight') !== FALSE) { ?>
        <?php print t('Book Tickets - Expires') ?>
        <?php }else{ ?>
        <?php print t('Book Tour Before') ?>
        <?php } ?>

        </strong><span><?php print format_date(strtotime($node->field_bookdates[0]['value']),'custom','d.m.Y') . " - " . format_date(strtotime($node->field_bookdates[0]['value2']),'custom','d.m.Y'); ?></span>

		</li>
      </ul>
	  </div>

	  <div class="frame-wrapper">
      <h2 class="inner6"><?php if (strpos($node->field_dealtype[0]['value'], 'Hotel') !== FALSE) { print t('Hotel Description'); } else { print t('Description'); } ?></h2>
      <div class="hotelblog-leftcontentblog1 hotelblog-leftcontentbloginner4 inner7">
      <span class="leftcontentblog1text"> <?php print $node->content['body']['#value']; ?>
      </span>
      </div>
	  </div>

      <?php if ($flight_policy && strpos($node->field_dealtype[0]['value'], 'Flight')!==FALSE) { ?>
	  <div class="frame-wrapper">
      <h2 class="inner4title3"><?php print t('Flight Deal Policy'); ?></h2>
      <div class="hotelblog-leftcontentblog1 hotelblog-leftcontentbloginner4">
      <span class="leftcontentblog1text"> <?php print $flight_policy; ?> </span>
      </div>
	  </div>
      <?php } ?>

    </div>
    <!-- Comment render -->
	<div class="frame-wrapper">
	<div class="reviews-blog b">
    <?php if ($node->field_alehap_review && !empty($node->field_alehap_review[0]['value'])) { ?>
		<h3 class="reviews-title"><span><?php print t('Alehap reviews') ?></span></h3>
		<div class="alehap-reviews">
			<?php print $node->field_alehap_review[0]['value'] ?>
		</div>
		<?php } ?>
    <div class="collapsible">
    <h3 class="reviews-title collapse-processed"><span><?php print t('User reviews') ?></span><a href="javascript:void()"></a></h3>
		<!-- facebook code -->
				<?php
				global $user; if (!$user->uid) {
				?>
				<div class="facebook-content-row">
					<div class="fb-left-cell">
						<div class="fb-nice-login-button">
		<!-- fb button start -->
		<?php

		$node_fb = node_load(30115);
		print $node_fb->body;
		?>
		<div class="fb-promor-comment-noac">
			<img src="<?php print base_path();?>sites/all/themes/alehap2/images/comment-login_noac.png" />
		</div>
		<!-- fb button end -->
						</div>
						<div class="fb-arrow ">
							<img src="<?php print base_path();?>sites/all/themes/alehap2/images/comment-login_arrow.png" />
						</div>
						<div class="clean"></div>
					</div>
					<div class="fb-right-cell">
						<img src="<?php print base_path();?>sites/all/themes/alehap2/images/comment-login_text.png" />
					</div>
				</div>

		<?php }?>
				<!-- facebook code end -->
				<div class="collapsible hotel-promo-comment-block">
					<!-- comment form render -->
					<?php
					if (function_exists('comment_render') && $node->comment) {
						print comment_render($node);
						$node->comment = NULL;
					}
				?>
				<!-- comment form render end -->
				</div>
		<!-- facebook code END -->
    </div>
    </div>
	</div>

    <div class="g-plusone-wrapper">
    	<g:plusone href="https://alehap.vn/<?php print $node->path; ?>"></g:plusone>
    </div>

    <div class="fb-like-button">
    	<fb:share-button href="http://alehap.vn/<?php print $node->path ?>" type="button"></fb:share-button>
		<fb:like <?php print $fbml_param; ?> layout="button_count" send="false" width="100" show_faces="false"></fb:like>
        <fb:comments <?php print $fbml_param; ?> width="654" numposts="10"></fb:comments>
	</div>

  </div>

  <div class="innerbotom-blog innerbotom-blog2 i">
    <h3 class="reviews-title reviews-title4 reviews-title5"><?php print t('Similar Deals') ?></h3>
      <?php
				//load the view by name
				print views_embed_view('similar_deals', $display_id = 'default', $node->nid, $node->field_mainplace[0]['nid']);
				?>
    </div>

</div>
<!-- This contains the hidden content for inline calls-->
<!--<div style="position:fixed; bottom:-1000px">
  <div id='inline_content'>
    <?php $gmap2 = gmap_location_block_view($node->nid); print $gmap2['content'];?>
  </div>
</div>-->

<?php } ?>
