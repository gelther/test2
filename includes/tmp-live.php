<?php
global $post, $is_live_page;
$is_live_page = TRUE;
$status = isset($_GET['force_show']) ? $_GET['force_show'] : get_post_meta($post->ID, '_wswebinar_gener_webinar_status', true);
$data = WebinarSysteemViews::get_livepage_data($post, $status);
extract($data);
if ($data_imgvid_type == 'youtube') {
    $autoplay = !empty($data_autoplay) ? '&autoplay=1' : '';
} else {
    $autoplay = !empty($data_autoplay) ? '?autoplay=1' : '';
}

$the_livep_title_color = empty($data_title_clr) ? '#000' : $data_title_clr;
?>
<html>

    <head>   
        <title><?php echo get_the_title(); ?></title>
        <meta property="og:title" content="<?php the_title(); ?>">
        <meta property="og:url" content="<?php echo get_permalink($post->ID); ?>">
        <meta property="og:description" content="<?php echo substr(wp_strip_all_tags(get_the_content(), true), 0, 500); ?>">
        <style>
            body.tmp-live{ <?php
                echo (empty($data_backg_clr)) ? '' : 'background-color:' . $data_backg_clr . ';';
                echo (empty($data_backg_img)) ? '' : 'background-image: url(' . $data_backg_img . ');';
                ?>}
                                               <?php if ($data_show_ques == 'yes'): ?>
                input[type="submit"]#saveQuestion{ box-shadow:none; border-style:solid; border-width:1px; <?php
                                                   echo (!empty($data_livep_button_radius) ) ? 'border-radius:' . $data_livep_button_radius . ';' : '';
                                                   echo (!empty($data_livep_button_bg_clr) ) ? 'background-color:' . $data_livep_button_bg_clr . ';' : '';
                                                   echo (!empty($data_livep_button_border_clr) ) ? 'border-color:' . $data_livep_button_border_clr . ';' : '';
                                                   echo (!empty($data_livep_button_text_clr) ) ? 'color:' . $data_livep_button_text_clr . ';' : '';
                                                   ?> }
                input[type="submit"]#saveQuestion:hover{ <?php
                    echo (!empty($data_livep_buttonhover_bg_clr) ) ? 'background-color:' . $data_livep_buttonhover_bg_clr . ';' : '';
                    echo (!empty($data_livep_buttonhover_border_clr) ) ? 'border-color:' . $data_livep_buttonhover_border_clr . ';' : '';
                    echo (!empty($data_livep_buttonhover_text_clr) ) ? 'color:' . $data_livep_buttonhover_text_clr . ';' : '';
                    ?> }
                <?php endif; ?>
                <?php
                global $is_live_page;
                if (isset($is_live_page) && $is_live_page && current_user_can('manage_options')):
                    ?>
                html{margin-top:32px !important;}
<?php endif; ?>
        </style>
        <script>wbnId = <?php echo $post->ID ?>; admin =<?php echo current_user_can('edit_posts') ? 'true' : 'false' ?></script>
<?php wp_head(); ?>
    </head>
    <body class="tmp-live">
        <div class="container">

            <!--[if lt IE 9]>
                <div style='row'>
                    <div class="col-xs-6 col-xs-offset-2">
                        <a href="http://www.microsoft.com/windows/internet-explorer/default.aspx">
                          <img src="<?php echo plugins_url('./images/iecheck.jpg', __FILE__); ?>" border="0" height="42" width="820" alt="" />
                        </a>
                    </div>
                </div>
            <![endif]-->

            <div class="row">
                <div class="col-lg-12 col-xs-12">
                    <div> 
                        <h1 class="text-center" style="font-weight: 400; color: <?php echo $the_livep_title_color; ?>;"><?php the_title(); ?></h1> 
                    </div>
                </div>
            </div>
            <div class="row" style="margin-top: 40px;">
                <div class="col-lg-12 col-sm-12 col-xs-12">
                    <div id="embed">
                        <?php if (empty($data_imgvid_url)) { ?>
                            <img src="<?php echo $data_defImgUrl; ?>" width="100%" height="315">
                            <?php
                        } else {
                            switch ($data_imgvid_type):
                                case 'image':
                                    echo '<img src="' . $data_imgvid_url . '" width="100%" height="500px">';
                                    break;
                                case 'youtube':
                                    $link = $data_imgvid_url;
                                    $youtubeid = WebinarSysteem::getYoutubeIdFromUrl($link);
                                    if ($youtubeid !== false) {
                                        echo '<iframe width="100%" height="563" src="//youtube.com/embed/' . $youtubeid . '?controls=0&rel=0&showinfo=0' . $autoplay . '" frameborder="0" allowfullscreen></iframe>';
                                    } elseif (!empty($link)) {
                                        echo '<iframe width="100%" height="563" src="//youtube.com/embed/' . $link . '?controls=0&rel=0&showinfo=0' . $autoplay . '" frameborder="0" allowfullscreen></iframe>';
                                    }
                                    break;
                                case 'vimeo':
                                    echo '<iframe src="https://player.vimeo.com/video/' . $data_imgvid_url . '' . $autoplay . '" width="100%" height="563" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen ' . $autoplay . '></iframe>';
                                    break;
                            endswitch;
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="row">
<?php if ($data_show_desc == 'yes' || $data_show_presenter == 'yes'): ?>
                    <div class="col-lg-5 col-sm-6 col-xs-12">
                        <div class="live-box signup round-border" style="margin-top: 10px; background-color: <?php echo $data_livep_leftbox_bckg_clr ?>; border-color:<?php echo $data_livep_leftbox_border_clr ?>">
    <?php if ($data_show_presenter == 'yes'): ?>
                                <div class="live-title" style="color:<?php echo $data_livep_hostbox_title_text_clr ?>; background-color: <?php echo $data_livep_hostbox_title_bckg_clr ?>;"><?php echo _n('Host', 'Hosts', $data_hostcount, WebinarSysteem::$lang_slug); ?></div> 
                                <div class="livep-content" style="color:<?php echo $data_livep_hostbox_content_text_clr ?>;"><?php
                                    foreach ($data_hostnames as $hostname) {
                                        echo esc_attr($hostname) . '<br/>';
                                    }
                                    ?></div>
                            <?php endif; ?>
    <?php if ($data_show_desc == 'yes'): ?>  
                                <div class="live-title" style="color:<?php echo $data_livep_descbox_title_text_clr ?>;background-color:<?php echo $data_livep_descbox_title_bckg_clr ?>;"><?php _e('Information', WebinarSysteem::$lang_slug) ?></div>
                                <div class="livep-content" style="color:<?php echo $data_livep_descbox_content_text_clr; ?>"><?php echo str_replace("\r", "<br />", get_the_content()); ?></div>
    <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-7 col-sm-6 col-xs-12" style="margin-bottom: 80px;">
                    <?php else : ?><div class="col-lg-12 col-sm-12 col-xs-12" style="margin-bottom: 80px;" >
<?php endif; ?>
                        <div id="wswebinar-live-page-ask-question-form" class="round-border signup" style="margin-top: 10px; background-color: <?php echo $data_livep_askq_bckg_clr ?>;border-color:<?php echo $data_livep_askq_border_clr ?>;<?php echo $data_show_ques == 'yes' ? '' : 'display:none;' ?>">
                            <h2 style="color:<?php echo $data_askq_title_text_clr; ?>;" class="live-title-sub"><?php _e('Ask your question!', WebinarSysteem::$lang_slug) ?></h2>
                            <div style="margin-left: 10px;">
<?php $cur_attendee_options = WebinarSysteemAttendees::getAttendee($post->ID); ?>
                                <form id="addQuestionForm">
                                    <div class="form-group">
                                        <input type="text" class="form-control" placeholder="<?php _e('Your name', WebinarSysteem::$lang_slug); ?>" id="que_name" name="que_name" value="<?php echo (empty($cur_attendee_options) ? '' : $cur_attendee_options->name); ?>">
                                    </div>
                                    <div class="form-group">
                                        <input type="text" class="form-control" placeholder="<?php _e('Your email', WebinarSysteem::$lang_slug); ?>" id="que_email" value="<?php echo (empty($cur_attendee_options) ? '' : $cur_attendee_options->email); ?>">
                                    </div>
                                    <div class="form-group">
                                        <textarea rows="4" cols="50" class="form-control" id="addQuestion" placeholder="<?php _e('Type your question here..', WebinarSysteem::$lang_slug); ?>" draggable></textarea>
                                    </div>
                                    <input type="submit" id="saveQuestion" class="btn btn-success" value="<?php _e('Ask Question!', WebinarSysteem::$lang_slug) ?>">
                                </form>
                            </div>
                            <div id="myQuestions" style="display:none;">
                                <h3 class="live-title-sub"><?php _e('My Questions', WebinarSysteem::$lang_slug) ?></h3>
                                <span id="ques_load"></span>
                            </div>                                
                        </div>
                        <?php
                        if ($data_show_incentive == 'yes'):
                            ?>
                            <div class="live-box signup round-border" style="margin-top: 10px; background-color: <?php echo $data_livep_incentive_bckg_clr ?>;border-color: <?php echo $data_livep_incentive_border_clr; ?>;">
                                <div class="live-title" style="color:<?php echo $data_livep_incentive_title_clr ?>;background-color: <?php echo $data_livep_incentive_title_bckg_clr ?>;"><?php echo $data_livep_incentive_title; ?></div>
                                <div class="livep-content"><?php echo apply_filters('the_content', $data_livep_incentive_content); ?></div>
                            </div>
<?php endif; ?>
                    </div>
                </div>

            </div>

            <script type="text/javascript">

            var theSaveQuestionButton;
            var theSaveQuestionButtonVal;
            var theWebinarId = <?php echo $post->ID; ?>;
            jQuery(document).on('click', '#saveQuestion', function (e) {
                var ques_name = jQuery('#que_name').val();
                var ques_email = jQuery('#que_email').val();
                var quest = jQuery('#addQuestion').val();
                if (ques_email.length < 3 || !validateEmail(ques_email) || ques_name.length < 1 || quest.length < 1) {
                    alert("<?php _e('Something is wrong with your Add Questions form. Please re-check all fields are filled correctly', WebinarSysteem::$lang_slug) ?>");
                    return false;
                }

                var datas = {'action': 'saveQuestionAjax', 'question': quest, 'name': jQuery('#que_name').val(),
                    'email': jQuery('#que_email').val(), 'webinar_id': <?php echo $post->ID; ?>};
                theSaveQuestionButton = jQuery(this);
                theSaveQuestionButtonVal = theSaveQuestionButton.val();
                jQuery(this).val("<?php _e('Please wait..', WebinarSysteem::$lang_slug) ?>");
                jQuery(this).attr('disabled', 'disabled');
                jQuery.ajax({type: 'POST', data: datas, url: ajaxurl, dataType: 'json'
                }).done(function (data) {
                    jQuery('#myQuestions').show();
                    theSaveQuestionButton.val(theSaveQuestionButtonVal);
                    theSaveQuestionButton.removeAttr('disabled');
                    jQuery('#addQuestion').val('');
                    addQuestionToPage("" + data.question, "" + data.time);
                });
                //e.preventDefault();
            });
            function addQuestionToPage(question, time) {
                jQuery('#ques_load').prepend(jQuery('<p class="myquestion"><span>' + time + '</span>' + question + '</p>').hide().fadeIn(2000));
            }

            function validateEmail(email) {
                var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                return re.test(email);
            }


            function loadViewerCount() {
                jQuery.ajax({
                    url: ajaxurl,
                    data: {action: 'getOnlineCount', 'webinar_id': theWebinarId},
                    dataType: 'json',
                    type: 'POST',
                }).done(function (response) {
                    if (response.status) {
                        jQuery('#webinar-live-viewers').html(response.data.count);
                    }

                });
            }

            function populateAttendeeNameList(data) {
                var list_li = '';
                for (x = 0; x < data.length; x++)
                    list_li += '<li><a href="#">' + data[x].name + '</a></li>';
                jQuery('#attendee-online-list').html(list_li);
            }

            function saveViewCount() {
                jQuery.ajax({
                    url: ajaxurl,
                    data: {action: 'updateLastSeen', webinar_id: theWebinarId},
                    dataType: 'json',
                    type: 'POST',
                });
            }

            jQuery(document).ready(function () {
                loadViewerCount();
            })

            setInterval(function () {
                saveViewCount();
                loadViewerCount();
            }, 10000);
            </script>
        </div>
<?php wp_footer(); ?> 


    </body>
</html>