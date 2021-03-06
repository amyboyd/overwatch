<?php

namespace Overwatch\UserBundle\Tests\Base;

use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\WebDriverCapabilityType;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverKeys;
use Overwatch\UserBundle\DataFixtures\ORM\UserFixtures;

/**
 * WebDriverTestCase
 * Extends DatabseAwareTestCase to add WebDriver logic.
 */
class WebDriverTestCase  extends DatabaseAwareTestCase {
    /**
     * @var RemoteWebDriver
     */
    protected $webDriver = NULL;
    
    public function setUp() {
        parent::setUp();
        
        $capabilities = [WebDriverCapabilityType::BROWSER_NAME => 'firefox'];
        $this->webDriver = RemoteWebDriver::create('http://localhost:4444/wd/hub', $capabilities);
    }
    
    public function runBare() {
        try {
            parent::runBare();
        } catch (TimeOutException $e) {
            //If there's a timeout the first time, retry, but don't catch
            //anything the second time around (i.e. there's only 1 retry attempt)
            parent::runBare();
        }
    }
    
    public function logInAsUser($userReference) {
        $user = UserFixtures::$users[$userReference];
        $this->webDriver->get("http://127.0.0.1:8000");
        $this->webDriver->findElement(WebDriverBy::id('username'))->clear();
        $this->webDriver->findElement(WebDriverBy::id('username'))->click();
        $this->webDriver->getKeyboard()->sendKeys($user->getEmail());
        $this->webDriver->findElement(WebDriverBy::id('password'))->click();
        $this->webDriver->getKeyboard()->sendKeys("p4ssw0rd");
        $this->webDriver->getKeyboard()->pressKey(WebDriverKeys::ENTER);
    }
    
    public function waitForUserInput() {
        if (trim(fgets(fopen("php://stdin", "r"))) != chr(13)) {
            return;
        }
    }
    
    public function waitForLoadingAnimation() {
        $this->webDriver->wait()->until(function($this) {
            return !$this->webDriver->findElement(WebDriverBy::id('loading'))->isDisplayed();
        });
    }
    
    public function waitForAlert() {
        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::alertIsPresent()
        );
    }
    
    public function getHeaderText() {
        return $this->webDriver->findElement(
            WebDriverBy::cssSelector("#page h1")
        )->getText();
    }
    
    public function tearDown() {
        parent::tearDown();
        
        if ($this->webDriver !== NULL) {
            $this->webDriver->close();
        }
    }
}
