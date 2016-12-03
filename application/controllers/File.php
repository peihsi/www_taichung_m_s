<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class File extends MY_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
	//public function index()
	//{
	//	$this->load->view('welcome_message');
	//}
	public function index()
	{
		$this->load->library('session');
		$User_Login = $this->User_Login("1");
		//echo $User_Login;
		if($User_Login == 1){
			$Login_ID = $this->session->userdata('Login_ID');
			$FullName = $this->session->userdata('FullName');
			$organization = $this->session->userdata('organization');
			$department = $this->session->userdata('department');
			$User_Level = $this->session->userdata('User_Level');
			$this->load->view('TEST', Array(
				'FullName' 		=> 	$FullName,
			));
		}
		else{
			$this->load->view('login');
		}

	}


	public function login()
	{
		$this->load->view('login');
	}

	public function check_boy_exist()		//檢查此役男是否存在
	{	
		$this->load->model('boy_model');		
		$ADF_code = $this->input->post('ADF_code');		
		$query = $this->boy_model->read_row_by_code($ADF_code);
		 	if ($query->num_rows() == 1){				//若抓到相同使用者成功
				echo json_encode("已存在");
			}
			else{
			    echo json_encode("不存在");
			}
	}

	/*
	* create a new military boy record, and initialize his subsidy file
	*/
	public function add_new_boy_file(){
		// create a new boy record
		$this->load->library('session');
		//var_dump($this->session);
		$FullName = $this->session->FullName;
 		$organization  = $this->session->organization;
      	$department  = $this->session->department;



		$this->load->model('boy_model');
		$name = $this->input->post('ADF_name');
		$id = $this->input->post('ADF_code');
		$birthday = $this->input->post('ADF_birthday');
		$begin_date = $this->input->post('ADF_milidate');
		$type = $this->input->post('ADF_type');
		$status = $this->input->post('ADF_status');
		
		$boy_key = $this->boy_model->add_new_boy($name, $id, $birthday, $begin_date, $type, $status);

		// create a new file record for this boy
		$this->load->model('file_model');
		$county = $this->input->post('ADF_county');
		$town = $this->input->post('ADF_town');
		$village = $this->input->post('ADF_village');
		$address = $this->input->post('ADF_address');
		$today = date("Y-m-d H:i:s");
		log_message('debug', print_r($today, true));

		$file_key = $this->file_model->add_new_file($today, $boy_key, $county, $town, $village, $address, $FullName, $organization, $department);
		
		$this->boy_model->update_new_boy_file_link($boy_key, $file_key);
		$data= array(
			'boy_key' => $boy_key,
			'file_key' => $file_key,
			'Msg' => "success"
			);

		echo json_encode($data);
	}

	public function read_new_file(){
		$file_key = (int)$this->input->post('file_key');
		$this->load->model('file_model');
		$file_info = $this->file_model->read_file($file_key);
		//var_dump($file_key);
		//var_dump($file_info);
		echo json_encode($file_info[0]);
	}

	//列出此承辦人待處理之案件
	public function read_file_list_pending(){	
		$this->load->library('session');
		//var_dump($this->session);
		$user_level = $this->session->User_Level;
		$user_organ = $this->session->organization;
		$this->load->model('file_model');
		$file_list = $this->file_model->read_file_list_pending($user_level, $user_organ);
		//var_dump($file_list);
		echo json_encode($file_list);
		//承辦人 LV 1 看自己區的編輯中(1)案件ㄝ, 民眾線上申請(2)的案件
		//科長LV2、主秘LV 3 ，可看到編輯完，跑流程中的案件
		//民政局承辦 LV4 科長 LV5 ，可看到編輯完，跑流程中的案件
		//LV 7 工程模式，全部狀態都能看到
	}

	//列出此公所已通過補助，役男尚未退役的之案件
	public function read_file_list_supporting(){

	}


	public function progress_next(){
		$file_key = (int)$this->input->post('file_key');
		$this->load->model('file_model');
		$file_info = $this->file_model->progress_file($file_key,"+");
		echo json_encode("Success");
	}

	public function progress_back(){
		$file_key = (int)$this->input->post('file_key');
		$this->load->model('file_model');
		$file_info = $this->file_model->progress_file($file_key,"1");
		echo json_encode("Success");
	}

// miliboy_table.入伍日期// <th style="width: 8em;">入伍日期</th>
// area_town.Town_name//   	<th style="width: 7em;">行政區</th>
// miliboy_table.役男姓名 //   	<th style="width: 7em;">役男姓名</th>
// miliboy_table.身分證字號//   	<th style="width: 7.5em;">役男證號</th>
// files_info_table.審批階段//   	<th style="width: 12em;">案件進度</th>
// files_info_table.扶助級別//   	<th style="width: 8em;">審查結果</th>
// files_info_table.建案日期//   	<th style="width: 7em;">立案日期</th>
// files_info_table.修改人姓名//   	<th style="width: 7em;">主要承辦人</th>
// files_info_table.案件流水號//    案件流水號
// files_info_table.可否編修//   	可否編輯	--可編輯者要多個編輯按鈕--   檢視-編輯-同意&呈核
// files_status_code.案件階段名稱//   	作業類別

}
