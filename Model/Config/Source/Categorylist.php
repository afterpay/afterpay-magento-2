<?php 
namespace Afterpay\Afterpay\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
  
class Categorylist implements ArrayInterface
{
    protected $_categoryHelper;
    protected $categoryRepository;
    protected $categoryList;
    protected $_request;
    protected $_storeManager;

    public function __construct(
        \Afterpay\Afterpay\Helper\Category $catalogCategory,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
		\Magento\Framework\App\Request\Http $request,
		\Magento\Store\Model\StoreManagerInterface $storeManager
        )
    {
        $this->_categoryHelper = $catalogCategory;
        $this->categoryRepository = $categoryRepository;
        $this->_request  = $request;
        $this->_storeManager  = $storeManager;
    }

    /*
     * Return categories helper
     */

    public function getStoreCategories($sorted = false, $asCollection = false, $toLoad = true)
    {
		$storeId = (int) $this->_request->getParam('store', 0);
        if (!isset($storeId) || empty($storeId)){
            $storeId=$this->_storeManager->getStore()->getId();
            $websiteId = (int) $this->_request->getParam('website', 0);
            
            if(isset($websiteId) && !empty($websiteId)){
                $storeId = $this->_storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
            }
        }
		
		return $this->_categoryHelper->getStoreCategories($sorted , $asCollection, $toLoad,$storeId);
		
    }

    /*  
     * Option getter
     * @return array
     */
    public function toOptionArray()
    {
        $arr = $this->toArray();
        $ret = [];
		
        if(!empty($arr)){
			foreach ($arr as $key => $value)
			{
				$ret[] = [
					'value' => $key,
					'label' => $value
				];
			}
		}

        return $ret;
    }

    /*
     * Get options in "key-value" format
     * @return array
     */
    public function toArray()
    {
        $categories = $this->getStoreCategories(true,false,true);
        $categoryList = $this->renderCategories($categories);
        return $categoryList;
    }

    public function renderCategories($_categories)
    {
        foreach ($_categories as $category){
			if($category->getEntityId()!= '1'){
				$i = 0; 
				$this->categoryList[$category->getEntityId()] = __($category->getName());   // Main categories
				$list = $this->renderSubCat($category,$i);
			}
        }

        return $this->categoryList;     
    }

    public function renderSubCat($cat,$j){

        $categoryObj = $this->categoryRepository->get($cat->getId());

        $level = $categoryObj->getLevel();
        $arrow = str_repeat(". . . ", $level-1);
        $subcategories = $categoryObj->getChildrenCategories(); 

        foreach($subcategories as $subcategory) {
            $this->categoryList[$subcategory->getEntityId()] = __($arrow.$subcategory->getName()); 
            if($subcategory->hasChildren()) {
                $this->renderSubCat($subcategory,$j);
            }
        } 
        return $this->categoryList;
    }
}
?>