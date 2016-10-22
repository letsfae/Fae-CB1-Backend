<?php

namespace App\Api\v1\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Dingo\Api\Routing\Helpers;
use App\Users;
use App\Name_cards;
use App\Name_card_tags;
use App\NameCardsSaved;
use Validator;
use Dingo\Api\Exception\UpdateResourceFailedException;

class UserNameCardController extends Controller
{
    use Helpers;
    
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getNameCard($user_id)
    {
        $nameCard = Name_cards::find($user_id);
        if(is_null($nameCard))
        {
            return $this->response->errorNotFound();
        }
        $user = $nameCard->hasOneUser()->first();
        $tags = array();
        if($nameCard->tag_ids != null)
        {
            foreach (explode(';', $nameCard->tag_ids) as $tag)
            {
                $tag = Name_card_tags::find($tag);
                $tags[] = array('tag_id' => $tag->id, 'title' => $tag->title, 'color' => $tag->color);
            }
        }
        $info = array('nick_name' => $nameCard->nick_name, 'short_intro' => $nameCard->short_intro,
                'tags' => $tags, 'show_gender' => $nameCard->show_gender, 'show_age' => $nameCard->show_age);
        if($nameCard->show_gender)
        {
            $info['gender'] = $user->gender;
        }
        if($nameCard->show_age)
        {
            $birthDate = $user->birthday;
            $birthDate = explode("-", $birthDate);
            $age = (date("md", date("U", mktime(0, 0, 0, $birthDate[1], $birthDate[2], $birthDate[0]))) > date("md")
                    ? ((date("Y") - $birthDate[0]) - 1)
                    : (date("Y") - $birthDate[0]));
            $info['age'] = $age;
        }
        return $this->response->array($info);
    }

    public function getSelfNameCard()
    {
        return $this->getNameCard($this->request->self_user_id);
    }

    public function getAllTags()
    {
        $tags = Name_card_tags::all();
        $info = array();
        foreach ($tags as $tag)
        {
            $info[] = array('tag_id' => $tag->id, 'title' => $tag->title, 'color' => $tag->color);
        }
        return $this->response->array($info);
    }

    public function updateNameCard()
    {
        $this->updateNameCardValidation($this->request);
        $nameCard = Name_cards::find($this->request->self_user_id);
        if($this->request->has('nick_name'))
        {
            $nameCard->nick_name = $this->request->nick_name;
        }
        if($this->request->has('short_intro'))
        {
            $nameCard->short_intro = $this->request->short_intro;
        }
        if(!is_null($this->request->short_intro) && empty($this->request->short_intro))
        {
            $nameCard->short_intro = null;
        }
        if($this->request->has('tag_ids'))
        {
            foreach (explode(';', $this->request->tag_ids) as $tag)
            {
                if(is_null(Name_card_tags::find($tag)))
                {
                    return $this->response->errorBadRequest('tag doest not exist');
                }
            }   
            $nameCard->tag_ids = $this->request->tag_ids;
        }
        if($this->request->has('show_age'))
        {
            if(strtolower($this->request->show_age) == 'true')
            {
                $nameCard->show_age = true;
            }
            else
            {
                $nameCard->show_age = false;
            }
        }
        if($this->request->has('show_gender'))
        {
            if(strtolower($this->request->show_gender) == 'true')
            {
                $nameCard->show_gender = true;
            }
            else
            {
                $nameCard->show_gender = false;
            }
        }
        $nameCard->save();
        return $this->response->created();
    }

    public function saveNameCard($user_id)
    {
        if(!is_numeric($user_id))
        {
            return $this->response->errorBadRequest("user_id is not integer");
        }
        if(!Name_cards::where('user_id',$user_id)->exists())
        {
            return $this->response->errorNotFound();
        }
        if(NameCardsSaved::where('user_id', $this->request->self_user_id)->where('name_card_user_id', $user_id)->exists())
        {
            return $this->response->errorBadRequest("You have already saved this name card!");
        }
        $name_card_saved = new NameCardsSaved();
        $name_card_saved->user_id = $this->request->self_user_id;
        $name_card_saved->name_card_user_id = $user_id;
        $name_card_saved->save();
        return $this->response->created();
    }

    public function unsaveNameCard($user_id)
    {
        if(!is_numeric($user_id))
        {
            return $this->response->errorBadRequest("user_id is not integer");
        }
        $name_card_saved = NameCardsSaved::where('user_id', $this->request->self_user_id)
                                         ->where('name_card_user_id', $user_id)->first();
        if(is_null($name_card_saved))
        {
            return $this->response->errorNotFound();
        }
        $name_card_saved->delete();
        return $this->response->noContent();
    }

    public function getSavedNameCardList()
    {
        $name_cards_saved = NameCardsSaved::where('user_id', $this->request->self_user_id)->get();
        $info = array();
        foreach ($name_cards_saved as $name_card_saved) 
        {
            $info[] = array('name_card_user_id' => $name_card_saved->name_card_user_id, 
                            'created_at' => $name_card_saved->created_at->format('Y-m-d H:i:s'));
        }
        return $this->response->array($info);
    }

    private function updateNameCardValidation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nick_name' => 'filled|required_without_all:short_intro,tag_ids,show_age,show_gender|alpha_num:50',
            'short_intro' => 'filled|required_without_all:nick_name,tag_ids,show_age,show_gender|string|max:200',
            'tag_ids' => 'filled|required_without_all:nick_name,short_intro,show_age,show_gender|regex:/^(\d+\;){0,2}\d+$/',
            'show_age' => 'filled|required_without_all:nick_name,short_intro,tag_ids,show_gender|in:TRUE,True,true,FALSE,False,false',
            'show_gender' => 'filled|required_without_all:nick_name,short_intro,tag_ids,show_age|in:TRUE,True,true,FALSE,False,false'
        ]);
        if($validator->fails())
        {
            if(!is_null($request->short_intro) && empty($request->short_intro))
            {
                if($request->has('nick_name') || $request->has('tag_ids') || $request->has('show_age') || $request->has('show_gender'))
                {
                    $validator = Validator::make($request->all(), [
                        'nick_name' => 'filled|alpha_num:50',
                        'tag_ids' => 'filled|regex:/^(\d+\;){0,2}\d+$/',
                        'show_age' => 'filled|in:TRUE,True,true,FALSE,False,false',
                        'show_gender' => 'filled|in:TRUE,True,true,FALSE,False,false'
                    ]);
                    if($validator->fails())
                    {
                        throw new UpdateResourceFailedException('Could not update name card.',$validator->errors());
                    }
                }
                return;
            }
            throw new UpdateResourceFailedException('Could not update name card.',$validator->errors());
        }
    }
}
