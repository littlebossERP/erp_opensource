<?php
/**
 * SerializeBehavior class file.
 *
 * @author fanjs
 * 
 */
namespace yii\behaviors;

use yii\db\ActiveRecord;
use yii\base\Behavior;
/**
 * SerializeBehavior allows a model to specify some attributes to be
 * arrays and serialized upon save and unserialized after a Find() function
 * is called on the model.
 *
 *<pre>
 * public function behaviors()
 *	{
 *		return array(
 *			'SerializeBehavior' => array(
 *				'class' => SerializeBehavior::className(),
 *				'serialAttributes' => array('validator_options'),
 *			)
 *		);
 *	}
 * </pre>
 * 
*/
class SerializeBehavior extends Behavior {
	/**
	* @var array The name of the attribute(s) to serialize/unserialize
	*/
    public $serialAttributes = array();
    
    // 重载events() 使得在事件触发时，调用行为中的一些方法
    public function events()
    {
    	// 在EVENT_BEFORE_VALIDATE事件触发时，调用成员函数 beforeValidate
    	return [
    		ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
    		ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
    			
    		ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
    		ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
    			
    		ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
    	];
    }
	
	public function beforeSave() {		
		if (count($this->serialAttributes)) {
            foreach($this->serialAttributes as $attribute) {
                $_att = $this->owner->$attribute;
                
                // check if the attribute is an array, and serialize it
                if(is_array($_att)) {
                    $this->owner->$attribute = serialize($_att);			
                } else {
                    // if its a string, lets see if its unserializable, if not
                    // fuck it set it to null
                    if(is_scalar($_att)) {
                        $a = @unserialize($_att);
                        if($a === false) {
                            $this->owner->$attribute = null;
                        }
                    }
                }
            }
        }
	}
	
	/** convert the saved as a serialized string back into an array, cause
	 *  thats how we want to use it anyways ya know?
	 */
	public function afterSave()
	{
		if(count($this->serialAttributes)) {
			foreach($this->serialAttributes as $attribute) {
				$_att = $this->owner->$attribute;
				if(!empty($_att)
				   && is_scalar($_att)) {
					$a = @unserialize($_att);
					if($a !== false) {
						$this->owner->$attribute = $a;
					} else {
						$this->owner->$attribute = null;
					}
				}
			}			
		}
	}
    
    public function afterFind()
    {		
    	if(count($this->serialAttributes)) {
            foreach($this->serialAttributes as $attribute) {				
                $_att = $this->owner->$attribute;
                if(!empty($_att)
                   && is_scalar($_att)) {
                    $a = @unserialize($_att);					
                    if($a !== false) {
                        $this->owner->$attribute = $a;
                    } else {
						$this->owner->$attribute = array();
					}
                }
            }
        }
    }
}
