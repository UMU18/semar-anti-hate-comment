<?php
/*
Plugin Name: Anti Hate Comment
Plugin URI:
Description: filter hate comment automatically
Author: UMU
Version: 1.0
Author URI:
*/

#Update database meta-comment onclik
if(isset($_POST['meta_value']) AND isset($_POST['comment_id']) ) {
    $comment_id = sanitize_text_field($_POST['comment_id']);
    $meta_value = sanitize_text_field($_POST['meta_value']);

    update_comment_meta( $comment_id, '_HateSpeechFilterComment', $meta_value );

    semar_anti_hate_comment_send_feedback($comment_id);
    exit();
}

if(isset($_POST['mydata']) AND isset($_POST['value'])){
    $value = sanitize_text_field($_POST['value']);
    $comment_id = sanitize_text_field($_POST['mydata']);

    foreach( $comment_id as $id){
    update_comment_meta( $id, '_HateSpeechFilterComment', $value );
    semar_anti_hate_comment_send_feedback($id);
    }

    exit();
}

#call when plugin activation and deactivation
register_activation_hook( __FILE__, 'semar_anti_hate_comment_activation' );
register_uninstall_hook( __FILE__, 'semar_anti_hate_comment_uninstall' );

function semar_anti_hate_comment_activation(){
    foreach (get_comments() as $data) {
        $exist = metadata_exists('comment', $data->comment_ID, '_HateSpeechFilterComment');

        if(! $exist){
            $safe = semar_anti_hate_comment_checking_comments($data->comment_content);
        if ($safe){
            $predict =1;
        }
       else{
        $predict =0;
           }
    add_comment_meta( $data->comment_ID, '_HateSpeechFilterComment', $predict);
        }
        
   }
}

function semar_anti_hate_comment_uninstall(){
    global $wpdb;
    $table = $wpdb->prefix.'commentmeta';
    $wpdb->delete ($table, array('meta_key' => '_HateSpeechFilterComment'));
}

#Display in admin menu
function semar_anti_hate_comment_set_admin_menu(){
    if (current_user_can('manage_categories')){
    add_menu_page('Anti-Hate-Comment', 'Anti-Hate-Comment', 'manage_options', 'coba_comment', 'semar_anti_hate_comment_display_managing_comment');}
}


add_action('admin_menu', 'semar_anti_hate_comment_set_admin_menu');

function semar_anti_hate_comment_display_managing_comment()
{ ?>
    <style>
        .category1:hover, .category1:active{color: red;}
        .category1{color:blue;}
        .category0:hover, .category0:active{color: blue;}
        .category0{color:red;}
    </style>
    <div class="container">
        <h1>MANAGE HATE COMMENT</h1>
        <form name="frm-example" name="frm-example" method="post">
        <div class="row">
            <div class="col-sm-3">
                <div class="form-group">
                    <select class="form-control" id="hateTableSelector">
                        <option data-selected="1">Safe</option>
                        <option data-selected="0">Not Safe</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-3">
                <input type="submit" class="button" name="submit" id="change" value="APPLY"/>
            </div>
        </div>
       
             <table id="hateTable" class="table table-bordered table-striped" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>
                        <div class="checkbox"><input type="checkbox" class="dt-checkboxes" id="table-select-all"><label></label></div>
                        </th>
                        <th>Author</th>
                        <th>Submited On</th>
                        <th>Comment</th>
                        <th>Category</th>
                    </tr>
                 </thead>
                 <tfoot>
                    <tr>
                        <th></th>
                        <th>Author</th>
                        <th>Submited On</th>
                        <th>Comment</th>
                        <th>Category</th>
                    </tr>
                 </tfoot>
                 <tbody>
                 <?php
                 global $wpdb;
                 $meta_comment = $wpdb->get_results ( "SELECT * FROM wp_commentmeta WHERE meta_key = '_HateSpeechFilterComment'" );
                 
                 ?>
                 <?php foreach ($meta_comment as $data): ?>
                 <?php $comment = get_comment( intval( $data->comment_id ) );?>
                    <tr>
                    <td>
                    <div class="checkbox"><input type="checkbox" class="dt-checkboxes mydata_checkbox" name="id[]" value="<?php echo $comment->comment_ID;?>"><label></label></div>    
                    </td>
                        <td><?php echo $comment->comment_author; ?></td>
                        <td><?php echo $comment->comment_date; ?></td>
                        <td><?php echo $comment->comment_content; ?></td>
                        <td>
                           <p id="id<?php echo $comment->comment_ID;?>" class="category<?php echo esc_attr($data->meta_value); ?>" onclick="doChange(this , <?php echo $comment->comment_ID; ?>)">
                        <?php if ($data->meta_value == 1):  ?>
                        <?php echo 'Safe' ?>
                        <?php else: ?>
                        <?php echo 'Not Safe' ?>
                        <?php endif ?>
                        </p> 
                        </td>
                    </tr>
                <?php endforeach; ?>    
                </tbody>
            </table>
   </form>
    </div>
<?php
 
}

#send data to python server, add data_latih 
function semar_anti_hate_comment_send_feedback($id){
$predict ='';
$post_url = 'http://semar.herokuapp.com/feedback';
 
global $wpdb;
        $comment_meta = $wpdb->get_results ( "SELECT * FROM wp_commentmeta WHERE comment_id = $id" );

    $comment = get_comment( intval( $id ) );
    $string = trim(preg_replace('/\s+/', ' ', $comment->comment_content));
 
    if ($comment_meta->meta_value == 1){
            $predict ='safe';
        }
        else{
            $predict ='notsafe';
            }
    $data_push = array('predict' => $predict, 'message' => $string);
    $data = json_encode( $data_push);
    $args = array( 'headers' => array( 'Content-Type' => 'application/json' ), 'body' => $data );
    $response = wp_remote_post( esc_url_raw( $post_url ), $args );
}  
 
# check comment is safe or notsafe
function semar_anti_hate_comment_checking_comments($comment) {
    $new = "message=" .urlencode($comment). "&";
    $request = wp_remote_get( "http://semar.herokuapp.com/predict?$new");
 
    if( is_wp_error( $request ) ) {
        return false;
    }
 
    $body = wp_remote_retrieve_body( $request );
 
    if( is_wp_error( $request ) ) {
        return false;
    }
 
    $data = json_decode( $body );
 
    if($data[0]->predict == 'safe') return true;
    return false;
}

# filter content-comment automatic
function semar_anti_hate_comment_blocking_hate_comment( $comment_text) {
    if ( is_admin() ) {
        return $comment_text;
    }
    $comment_id = get_comment_ID();
    $safe = semar_anti_hate_comment_checking_category($comment_id);
        if (!$safe){
            $block = plugin_dir_url(__FILE__).'images/nohate.png';
            return "<p><img src=".$block." alt='block hate comment' style='float:left;width:50px;height:50px;'/><i>sorry we block this comment due to <b>offensive content</b></i><br> please contact administrator</p>";
        }
    return $comment_text;
}
add_filter( 'comment_text', 'semar_anti_hate_comment_blocking_hate_comment');

function semar_anti_hate_comment_checking_category($data){
$status = get_comment_meta( $data, '_HateSpeechFilterComment', true );
if ($status==1){
    return true;
}
else{
    return false;
}
}
# insert new comment meta data into comment-meta 
function semar_anti_hate_comment_filter_new_comment($comment_id){
    
    $comment = get_comment( intval( $comment_id ) );
 
    $safe = semar_anti_hate_comment_checking_comments($comment->comment_content);
        if ($safe){
            $predict =1;
        }
        else{
            $predict =0;
            }
 
    add_comment_meta( $comment_id, '_HateSpeechFilterComment', $predict );    
}
add_action( 'comment_post', 'semar_anti_hate_comment_filter_new_comment' );

# create link setting in plugin homepage
function semar_anti_hate_comment_plugin_settings_link( $links, $file ) {

    if ( $file == plugin_basename( __FILE__ ) ) {
        $pccf_links = '<a href="' . get_admin_url() . 'admin.php?page=coba_comment">' . __( 'Manage' ) . '</a>';
        array_unshift( $links, $pccf_links );
    }

    return $links;
}
add_filter( 'plugin_action_links', 'semar_anti_hate_comment_plugin_settings_link', 10, 2 );

# enqueue admin scripts and style
function semar_anti_hate_comment_enqueue_admin_scripts() {
     wp_enqueue_script( 'anti-hate-comment-js',  plugin_dir_url( __FILE__ ) . 'js/anti_hate_comment.js' );

     wp_enqueue_script( 'show_datatables', plugin_dir_url(__FILE__). 'js/show-table.js', array(), '1.0', true );

     /*wp_register_style('datatables_css', 'https://cdn.datatables.net/v/bs-3.3.7/dt-1.10.15/se-1.2.2/datatables.min.css');*/
     wp_register_style('datatables_css', plugin_dir_url(__FILE__) . 'css/datatables.min.css' );
     wp_enqueue_style('datatables_css');

     /*wp_register_script('datatables_js', 'https://cdn.datatables.net/v/bs-3.3.7/dt-1.10.15/se-1.2.2/datatables.min.js', array('jquery'), true);*/
     wp_register_script('datatables_js', plugin_dir_url(__FILE__) . 'js/datatables.min.js', array('jquery'), true);
     wp_enqueue_script('datatables_js');

     /*wp_register_style('fontawesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');*/
     wp_register_style('fontawesome', plugin_dir_url(__FILE__) . 'css/font-awesome.min.css');
     wp_enqueue_style('fontawesome');

     /*wp_register_style('checkbox_css', 'https://cdnjs.cloudflare.com/ajax/libs/awesome-bootstrap-checkbox/0.3.7/awesome-bootstrap-checkbox.css');*/
     wp_register_style('checkbox_css', plugin_dir_url(__FILE__) . 'css/awesome-bootstrap-checkbox.css');
     wp_enqueue_style('checkbox_css');

     /*wp_register_style('datatables_checkbox_css', 'https://gyrocode.github.io/jquery-datatables-checkboxes/1.2.9/css/dataTables.checkboxes.css');*/
     wp_register_style('datatables_checkbox_css', plugin_dir_url(__FILE__) . 'css/dataTables.checkboxes.css');
     wp_enqueue_style('datatables_checkbox_css');

     /*wp_register_script('datatables_checkbox_js', 'https://gyrocode.github.io/jquery-datatables-checkboxes/1.2.9/js/dataTables.checkboxes.min.js', array('jquery'), true);*/
     wp_register_script('datatables_checkbox_js', plugin_dir_url(__FILE__) . 'js/dataTables.checkboxes.min.js', array('jquery'), true);
     wp_enqueue_script('datatables_checkbox_js');
 
}
add_action( 'admin_enqueue_scripts', 'semar_anti_hate_comment_enqueue_admin_scripts' );





