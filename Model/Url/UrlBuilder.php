<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Url;

class UrlBuilder
{
    const TYPE_API = 'api_url';
    const TYPE_JS_LIB = 'js_lib_url';
    const TYPE_WEB_JS_LIB = 'web_url';

    private $urlFactory;

    public function __construct(
        UrlBuilder\UrlFactory $urlFactory
    ) {
        $this->urlFactory = $urlFactory;
    }

    public function build(string $type, string $path, array $pathArgs = [], ?int $storeId = null): string
    {
        return $this->urlFactory->create($type, $storeId) . $this->replaceArgsInPath($path, $pathArgs);
    }

    private function replaceArgsInPath(string $path, array $args): string
    {
        foreach ($args as $argKey => $argVal) {
            $path = str_replace('{' . $argKey . '}', $argVal, $path);
        }
        return $path;
    }
}
