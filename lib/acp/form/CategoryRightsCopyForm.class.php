<?php
namespace lexicon\acp\form;
use lexicon\data\category\LexiconCategoryNodeTree;
use wcf\form\AbstractForm;
use wcf\system\acl\ACLHandler;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;

/**
 * Copies the category user group rights from one user group to another
 *
 * @author		Sonnenspeer
 * @copyright	2013 Sonnenspeer
 * @license		Creative Commons BY-ND <http://creativecommons.org/licenses/by-nd/4.0/legalcode>
 */
class CategoryRightsCopyForm extends AbstractForm  {
	/**
	 * @see	\wcf\page\AbstractPage::$activeMenuItem
	 */
	public $activeMenuItem = 'lexicon.acp.menu.link.lexicon.category.rights.copy';

	/**
	 * @see	\wcf\page\AbstractPage::$neededPermissions
	 */
	public $neededPermissions = array('admin.lexicon.canManageCategory');

	/**
	 * Source
	 */
	private $sourceCategoryID = null;

	/**
	 * Target
	 */
	private $targetCategoryIDs = array();

	/**
	 * object type id
	 * @var    integer
	 */
	public $objectTypeID = 0;

	/**
	 * @see	\wcf\page\IPage::readParameters()
	 */
	public function readParameters() {
		parent::readParameters();

		if (isset($_REQUEST['sourceCategoryID'])) $this->sourceCategoryID = intval($_REQUEST['sourceCategoryID']);
		if (isset($_REQUEST['targetCategoryIDs'])) $this->targetCategoryIDs = (empty($_REQUEST['targetCategoryIDs'])) ? array() : $_REQUEST['targetCategoryIDs'];

		$this->objectTypeID = ACLHandler::getInstance()->getObjectTypeID('com.viecode.lexicon.category');
	}

	/**
	 * @see    \wcf\form\IForm::validate()
	 */
	public function validate() {
		parent::validate();

		$this->validateSourceGroupID();

		$this->validateTargetGroupIDs();
	}

	/**
	 * Validate if source is empty nor
	 */
	protected function validateSourceGroupID() {
		if ($this->sourceCategoryID == null) {
			throw new UserInputException('sourceCategoryID', 'noSelection');
		}
	}

	/**
	 * Validate if target is empty nor
	 */
	protected function validateTargetGroupIDs() {
		if (count($this->targetCategoryIDs) == 0) {
			throw new UserInputException('targetCategoryIDs', 'noSelection');
		}
	}

	/**
	 * @see	\wcf\form\IForm::save()
	 */
	public function save() {
		parent::save();

		// load source permissions
		$source = ACLHandler::getInstance()->getPermissions($this->objectTypeID, array($this->sourceCategoryID));

		// simulate post
		$_POST['aclValues'] = array();
		$_POST['aclValues']['group'] = array();
		$_POST['aclValues']['user'] = array();

		if(count($source['group']) > 0) $_POST['aclValues']['group'] = $source['group'][$this->sourceCategoryID];
		if(count($source['user']) > 0) $_POST['aclValues']['user'] = $source['user'][$this->sourceCategoryID];

		// save ACL each target board
		foreach($this->targetCategoryIDs AS $target) {
			ACLHandler::getInstance()->save($target, $this->objectTypeID);
		}

		ACLHandler::getInstance()->disableAssignVariables();
		$this->saved();

		// show success
		WCF::getTPL()->assign(array(
			'success' => true
		));
	}

	/**
	 * @see	\wcf\page\IPage::assignVariables()
	 */
	public function assignVariables() {
		parent::assignVariables();

		$categoryTree = new lexiconCategoryNodeTree('com.viecode.lexicon.category', 0, true, array());
		$categoryTree->setMaxDepth(3);
		$categoryList = $categoryTree->getIterator();

		WCF::getTPL()->assign(array(
			'categoryList' => $categoryList,
			'sourceCategoryID' => $this->sourceCategoryID
		));
	}
}
