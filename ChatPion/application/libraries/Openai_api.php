<?php 

class Openai_api 
{
    public function open_ai_completion($api_key,$prompt,$model="text-davinci-003",$max_tokens=1500,$instruction="AI Agent",$description="description in the flow",$human){


      $text_completion_model=array("text-davinci-003", "text-davinci-002", "text-curie-001", "text-babbage-001", 
                                    "text-ada-001", "davinci", "curie", "babbage", "ada");

      $chat_completion_model=array("gpt-4", "gpt-4-0314", "gpt-4-32k", "gpt-4-32k-0314", "gpt-3.5-turbo", "gpt-3.5-turbo-0301");

      $completion="text";

      if(in_array($model,$text_completion_model))
            $completion="text";
      else if (in_array($model,$chat_completion_model))
            $completion="chat";


      if($completion=="text"){
            $data['model']= $model;
            $data['prompt']= $prompt;
            $data['max_tokens']= $max_tokens;
            $data['temperature']= 0.4;
            $data['top_p']= 1;
            $data['frequency_penalty']= 0;
            $data['presence_penalty']= 0;
            $url="https://api.openai.com/v1/completions";
      }

      else{
            $data['model']=$model;
            $data['max_tokens']= $max_tokens;
            $system_content=$instruction.".".$description;
            $data['messages']=array(array("role"=>"system","content"=>$system_content),
                        array("role"=>"user","content"=>$human));
            $url="https://api.openai.com/v1/chat/completions";
      }


      $data=json_encode($data);

      $headers=array("Content-Type: application/json","Authorization: Bearer {$api_key}");

      
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      $result = curl_exec($ch);


      if($completion=="chat"){
            $result_array=json_decode($result,true);
            $response= $result_array['choices'][0]['message']['content'];
            $result_array['choices'][0]['text']=$response;
            $result=json_encode($result_array);
            return $result;
      }

      return $result;



    }


}