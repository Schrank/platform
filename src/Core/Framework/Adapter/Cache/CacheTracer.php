<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Adapter\Cache;

use Shopware\Core\Framework\Adapter\Translation\AbstractTranslator;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class CacheTracer extends AbstractCacheTracer
{
    private SystemConfigService $config;

    private AbstractTranslator $translator;

    public function __construct(SystemConfigService $config, AbstractTranslator $translator)
    {
        $this->config = $config;
        $this->translator = $translator;
    }

    public function getDecorated(): AbstractCacheTracer
    {
        throw new DecorationPatternException(self::class);
    }

    public function trace(string $key, \Closure $param)
    {
        return $this->translator->trace($key, function () use ($key, $param) {
            return $this->config->trace($key, $param);
        });
    }

    public function get(string $key): array
    {
        return array_merge(
            $this->config->getTrace($key),
            $this->translator->getTrace($key)
        );
    }
}
