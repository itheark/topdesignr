<?php
class Main extends CI_Controller {

    function __construct()
    {
        parent:: __construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->helper('form');
        $this->load->helper('date');
        $this->load->model('user_model');
        $data['error']='';
        $error='';
        $this->load->library('image_lib');



    }
    function home(){

            if($this->session->userdata('logged_in')){
              if($this->session->userdata('role')!='admin'){
            $data['post']= $this->user_model->get_post();
            $this->load->view('home',$data);}
            else{
                redirect('admin/home');
            }
        }else{

            $this->load->view('main');
        }

    }

    function signup(){


        $this->load->model('user_model');

        $this->load->database();

        if($this->session->userdata('logged_in'))
        {
            redirect('main/home');
        }
        else
        {

            $data['error'] = '';

            if($this->input->post('submit'))
            {

                $this->load->library('form_validation');
                $this->form_validation->set_rules('uname', 'Username', 'required');
                $this->form_validation->set_rules('email', 'Email',  'required|valid_email');
                $this->form_validation->set_rules('pwd', 'Password', 'required|matches[cpwd]');
                $this->form_validation->set_rules('cpwd', 'Confirm Password', 'required');

                if($this->form_validation->run() == FALSE)
                {

                    $this->load->view('register',$data);
                }

                else
                {

                    $username = $this->input->post('uname');
                    $email = $this->input->post('email');
                    $check = "SELECT * FROM user where uname = '$username' OR email = '$email'";
                    $query = $this->db->query($check);

                    if($query->num_rows()>0)
                    {
                        $data['error'] = "The username or email address you entered is already in use <br/>";
                        $this->load->view('register',$data);
                    }
                    else
                    {
                        $salt = $this->user_model->generate_salt();
                        $new_pwd =$this->user_model->encrypt($salt,$this->input->post('pwd'));
                        $user = $this->user_model->new_user($salt,$new_pwd);
                        $this->user_model->follow_admin($user);

                        redirect('main/login');
                    }
                }

            }

            else
            {
                $this->load->view('register',$data);
            }
        }
    }

    function login(){

        $data['error']='';
        if($this->session->userdata('uname'!=""))
        {
            redirect('main/home');
        }

        $session_set_value = $this->session->all_userdata();

        if (isset($session_set_value['remember_me']) && $session_set_value['remember_me'] == "1")
        {
            redirect('main/home');
        }
        else
        {

            if($this->input->post('login_sub'))
            {

                $this->load->library('form_validation');
                $this->form_validation->set_rules('email', 'Email',  'required|valid_email');
                $this->form_validation->set_rules('pwd', 'Password', 'required');

                if($this->form_validation->run()== FALSE)
                {
                    $this->load->view('login',$data);
                }
                else
                {
                    $email = $this->input->post('email');
                    $pwd = $this->input->post('pwd');
                    $res = $this->user_model->login_user($email,$pwd);
                    if($res)
                    {
                        $role = $this->session->userdata('role');
                        $remem = $this->input->post('remember_me');
                        if($remem)
                        {
                            $this->session->set_userdata('remember_me', TRUE);
                        }
                        if($role=='admin')
                        {
                            redirect('admin/home');
                        }
                        else
                        {
                            redirect('main/home');
                        }
                    }

                    else
                    {
                        $data['error'] = "Error occured. Try logging in again or Contact Administrator! </br>";
                        $this->load->view('login',$data);
                    }
                }

            }

            else
            {
                $this->load->view('login',$data);

            }
        }

    }

    function explore(){
          if($this->session->userdata('logged_in')){
            $data['post']= $this->user_model->explore();
            $this->load->view('explorenew',$data);
        }else{

            $this->load->view('explore');
        }

    }
    function exploredetails($id=0){
        $data['post']= $this->user_model->post_details($id);
        $this->load->view('exploredetails',$data);
    }

    function compete($cat='All'){
        $data['catname'] =$cat;
        $data['cat'] = $this->user_model->category();
        $data['query'] =$this->user_model->compete($cat);
        $this->load->view('compete',$data);

    }

    function competedetails($id=0){
        if($this->session->userdata('logged_in')){
                     $data['query']= $this->user_model->competedetails($id);
                    $id1=$id;
                    $id2= $this->session->userdata('user_id');
                    $check = "SELECT * FROM submission where c_id = '$id1' AND user_id = '$id2'";
                    $query = $this->db->query($check);

                    if($query->num_rows()>0)
                    {
                        $data['submitted']= TRUE;
                        }
                    else{
                        $data['submitted']= FALSE;
                    }

       $this->load->view('competedetails',$data);}
       else
       {
        redirect('main/login');
       }
   }

   function submission($id=0)
   {


    if($this->input->post('filesubmit'))
    {
            $filesCount = count($_FILES['userFiles']['name']);
            $path = "uploads/competition/".$this->session->userdata('c_id')."/".$this->session->userdata('user_id');
            mkdir($path,0755,TRUE);
            for($i = 0; $i < $filesCount; $i++){
                $_FILES['userFile']['name'] = $_FILES['userFiles']['name'][$i];
                $_FILES['userFile']['type'] = $_FILES['userFiles']['type'][$i];
                $_FILES['userFile']['tmp_name'] = $_FILES['userFiles']['tmp_name'][$i];
                $_FILES['userFile']['error'] = $_FILES['userFiles']['error'][$i];
                $_FILES['userFile']['size'] = $_FILES['userFiles']['size'][$i];

                $uploadPath = 'uploads/competition/'.$this->session->userdata('c_id')."/".$this->session->userdata('user_id');
                $config['upload_path'] = $uploadPath;
                $config['allowed_types'] = 'jpg|png';

                $this->load->library('upload', $config);
                $this->upload->initialize($config);
                if($this->upload->do_upload('userFile')){
                    $fileData = $this->upload->data();
                    $uploadData[$i]['file_name'] = $fileData['file_name'];
                    $uploadData[$i]['created'] = date("Y-m-d H:i:s");
                    $uploadData[$i]['modified'] = date("Y-m-d H:i:s");
                }
                else{
                $error = array('error' => $this->upload->display_errors());

                $this->load->view('submission', $error);
                }
            }
            if(!empty($uploadData)){
                $this->user_model->submission();
                redirect('main/compete');

            }




    }
    else{
        $this->session->set_userdata(array('c_id'=> $id,));
        $this->load->view('submission',array('error' => ' ' ));
    }

   }

   function logout()
   {
    $data = array(
        'user_id'=>'',
        'uname' => '',
        'email' => '',
        'logged_in' => FALSE,);
    $this->session->unset_userdata($data);
    $this->session->sess_destroy();
    redirect('main/home');

}

function profile($name='')
{
    if($this->session->userdata('logged_in')){
   $data['user'] = $this->user_model->profile($name);

                    $user=$this->user_model->get_userid($name);
                    $id1=$user['user_id'];
                    $id2= $this->session->userdata('user_id');
                    $check = "SELECT * FROM follow where user_id = '$id2' AND followee_id = '$id1'";
                    $query = $this->db->query($check);

                    if($query->num_rows()>0)
                    {
                        $data['following']= TRUE;
                        }
                    else{
                        $data['following']= FALSE;
                    }
    $data['post'] = $this->user_model->get_userpost($id1);
    $this->load->view('profile',$data);
}
else
{
    redirect('main/login');
}

}

function profile_pic()
{
    if($this->input->post('submit'))
    {



            $file_name = $this->session->userdata('user_id');

            $config =  array(
              'upload_path'     => "./uploads/profile",
              'allowed_types'   => "jpg|png|jpeg",
              'overwrite'       => TRUE,
                  'max_size'        => "2048",  // Can be set to particular file size
                  'max_height'      => "4096",
                  'max_width'       => "4096" ,
                  'file_name'       =>  $file_name
                  );

            $this->load->library('upload', $config);
            if($this->upload->do_upload())
            {


                $data = array('upload_data' => $this->upload->data());

                   //FOr Main Image

                $this->user_model->add_pic($data['upload_data'],$file_name);

                $config['image_library'] = 'gd2';
                $config['source_image'] = './uploads/profile/'.$data['upload_data']['file_name'];
                    //$config['create_thumb'] = TRUE;
                $config['maintain_ratio'] = TRUE;
                $config['width']     = 200;
                $config['height']   = 200;
                $this->image_lib->clear();
                $this->image_lib->initialize($config);
                $this->image_lib->resize();
                    //For Thumbnail

                $config['image_library'] = 'gd2';
                $config['source_image'] = './uploads/profile/'.$data['upload_data']['file_name'];
                    //$config['create_thumb'] = TRUE;
                $config['maintain_ratio'] = TRUE;
                $config['new_image'] = './uploads/profile/thumb/'.$data['upload_data']['file_name'];
                $config['width']     = 50;
                $config['height']   = 50;




                $this->image_lib->clear();
                $this->image_lib->initialize($config);
                $this->image_lib->resize();





                redirect('main/profile/'.$this->session->userdata('uname'));
            }
            else
            {
                $error = array('error' => $this->upload->display_errors());

                $this->load->view('profile_pic', $error);
            }


        }

    else{
        $this->load->view('profile_pic',array('error' => ' ' ));
    }
}
function follow($id)
{

        $this->user_model->follow($id);
        $name= $this->user_model->get_uname($id);
        redirect('main/profile/'.$name['uname']);


}
function unfollow($id)
{
    $this->user_model->unfollow($id);
        $name= $this->user_model->get_uname($id);
        redirect('main/profile/'.$name['uname']);

}


public function post_upload(){

    if($this->input->post('submit'))
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('post_title', 'title',  'required');
        if($this->form_validation->run()== FALSE)
        {
            $this->load->view('post_upload',array('error' => ' ' ));
        }
        else
        {

            $file_name = $this->user_model->new_post();

            $config =  array(
              'upload_path'     => "./uploads/post",
              'allowed_types'   => "jpg|png|jpeg",
              'overwrite'       => TRUE,
                  'max_size'        => "2048",  // Can be set to particular file size
                  'max_height'      => "4096",
                  'max_width'       => "4096" ,
                  'file_name'       =>  $file_name
                  );

            $this->load->library('upload', $config);
            if($this->upload->do_upload())
            {


                $data = array('upload_data' => $this->upload->data());

                   //FOr Main Image

                $this->user_model->add_file($data['upload_data'],$file_name);

                $config['image_library'] = 'gd2';
                $config['source_image'] = './uploads/post/'.$data['upload_data']['file_name'];
                    //$config['create_thumb'] = TRUE;
                $config['maintain_ratio'] = TRUE;
                $config['width']     = 577;
                $config['height']   = 552;
                $this->image_lib->clear();
                $this->image_lib->initialize($config);
                $this->image_lib->resize();
                    //For Thumbnail

                $config['image_library'] = 'gd2';
                $config['source_image'] = './uploads/post/'.$data['upload_data']['file_name'];
                    //$config['create_thumb'] = TRUE;
                $config['maintain_ratio'] = TRUE;
                $config['new_image'] = './uploads/post/thumb/'.$data['upload_data']['file_name'];
                $config['width']     = 269;
                $config['height']   = 261;




                $this->image_lib->clear();
                $this->image_lib->initialize($config);
                $this->image_lib->resize();





                redirect('main/home');
            }
            else
            {
                $error = array('error' => $this->upload->display_errors());
                $this->user_model->delete_post($file_name);
                $this->load->view('post_upload', $error);
            }


        }
    }
    else{
        $this->load->view('post_upload',array('error' => ' ' ));
    }
}

public function index() {
  $this->load->view('main');
}


}
?>
