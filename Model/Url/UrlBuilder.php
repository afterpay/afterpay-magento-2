<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Url;

class UrlBuilder
{
    public const TYPE_API = 'api_url';
    public const TYPE_JS_LIB = 'js_lib_url';


    private $urlFactory;

    public function __construct(
        UrlBuilder\UrlFactory $urlFactory
    ) {
        $this->urlFactory = $urlFactory;
    }

    public function build(string $type, string $path, array $pathArgs = [], ?int $storeId = null): string
    {
        return $this->urlFactory->create($type, $storeId, $pathArgs) . $this->replaceArgsInPath($path, $pathArgs);
    }

    private function replaceArgsInPath(string $path, array $args): string
    {
        foreach ($args as $argKey => $argVal) {
            $path = str_replace('{' . $argKey . '}', (string)$argVal, $path);
        }
        return $path;
    }
}
