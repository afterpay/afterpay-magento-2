<?php 
namespace Afterpay\Afterpay\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
  
class Categorylist implements ArrayInterface
{
    protected $_categoryHelper;
    protected $categoryRepository;
    protected $categoryList;

    public function __construct(
        \Magento\Catalog\Helper\Category $catalogCategory,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository
        )
    {
        $this->_categoryHelper = $catalogCategory;
        $this->categoryRepository = $categoryRepository;
    }

    /*
     * Return categories helper
     */

    public function getStoreCategories($sorted = false, $asCollection = false, $toLoad = true)
    {
        return $this->_categoryHelper->getStoreCategories($sorted , $asCollection, $toLoad);
    }

    /*  
     * Option getter
     * @return array
     */
    public function toOptionArray()
    {
        $arr = $this->toArray();
        $ret = [];

        foreach ($arr as $key => $value)
        {
            $ret[] = [
                'value' => $key,
                'label' => $value
            ];
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
            $i = 0; 
            $this->categoryList[$category->getEntityId()] = __($category->getName());   // Main categories
            $list = $this->renderSubCat($category,$i);
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