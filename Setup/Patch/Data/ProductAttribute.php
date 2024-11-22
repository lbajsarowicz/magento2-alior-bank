<?php 

namespace AliorBank\Raty\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetup; 
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface; 
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Eav\Model\Config;

    
class ProductAttribute implements DataPatchInterface { 
    private $moduleDataSetup; 
    private $eavSetupFactory; 

    public function __construct( ModuleDataSetupInterface $moduleDataSetup, EavSetupFactory $eavSetupFactory ) { 
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY, 'aliorbank_product_promotion',
            [
                'type' => 'int',
                'label' => 'Włącz ofertę specjalną dla tego Produktu',
                'input' => 'boolean',
                'global' => ScopedAttributeInterface::SCOPE_STORE,
				'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'visible' => true,
				'required' => false,
                'user_defined' => true,
                'unique' => false,
                'group' => 'AliorBank'
            ]
		);
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}