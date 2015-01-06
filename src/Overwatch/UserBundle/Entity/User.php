<?php

namespace Overwatch\UserBundle\Entity;

use FOS\UserBundle\Entity\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Overwatch\UserBundle\Enum\AlertSetting;
use Overwatch\ResultBundle\Enum\ResultStatus;

/**
 * User
 * 
 * @ORM\Entity
 * @ORM\Table(name="User")
 */
class User extends BaseUser implements \JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\ManyToMany(targetEntity="Overwatch\TestBundle\Entity\TestGroup", inversedBy="users")
     * @ORM\JoinTable(name="UsersTestGroups")
     */
    protected $groups;
    
    /**
     * @ORM\Column(type="integer")
     */
    protected $alertSetting = AlertSetting::CHANGE;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->groups = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Serialize object to JSON
     */
    public function jsonSerialize() {
        return [
            "id" => $this->getId(),
            "email" => $this->getEmail(),
            "alertSetting" => $this->getAlertSetting(),
            "lastLogin" => $this->getLastLogin() ? $this->getLastLogin()->getTimestamp() : '',
            "locked" => $this->isLocked(),
            "roles" => $this->getRoles()
        ];
    }
    
    /**
     * Set Email (and username)
     * 
     * @param string $email
     * @return User
     */
    public function setEmail($email) {
        parent::setEmail($email);
        parent::setUsername($email);
        return $this;
    }
    
    /**
     * Get Alert Setting for this user
     * 
     * @return integer $alertSetting
     */
    public function getAlertSetting() {
        return $this->alertSetting;
    }
    
    /**
     * Set this user's alert setting
     * 
     * @param integer $setting
     * @return User
     */
    public function setAlertSetting($setting) {
        AlertSetting::isValid($setting);
        $this->alertSetting = $setting;
        
        return $this;
    }
    
    /**
     * Should this user be alerted for the passed TestResult?
     * 
     * @param \Overwatch\ResultBundle\Entity\TestResult $result
     * @return bool
     */
    public function shouldBeAlerted(\Overwatch\ResultBundle\Entity\TestResult $result) {
        $setting = $this->getAlertSetting();
        
        if ($this->isLocked() || $setting === AlertSetting::NONE) {
            return false;
        }
        
        if ($setting === AlertSetting::ALL) {
            return true;
        }
        
        if (
            in_array($result->getStatus(), [ResultStatus::ERROR, ResultStatus::FAILED])
            && ($setting === AlertSetting::CHANGE_ALL)
        ) {
            return true;
        }
        
        if ($result->isAChange()) {
            if (in_array($setting, [AlertSetting::CHANGE, AlertSetting::CHANGE_ALL])) {
                return true;
            }
            
            if (
                in_array($result->getStatus(), [ResultStatus::ERROR, ResultStatus::FAILED])
                && $setting === AlertSetting::CHANGE_BAD
            ) {
                return true;
            }
        }
        
        return false;
    }
}
