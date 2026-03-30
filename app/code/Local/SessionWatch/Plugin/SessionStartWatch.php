<?php
declare(strict_types=1);

namespace Local\SessionWatch\Plugin;

use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;

class SessionStartWatch
{
    /** Log only once per request */
    private bool $logged = false;

    public function __construct(
        private RequestInterface $request,
        private LoggerInterface  $logger
    ) {}

    /**
     * Intercepts \Magento\Framework\Session\SessionManager::start()
     * to log WHERE the session is being started from (stack),
     * but only for GET HTML pages and NOT for sensitive paths.
     *
     * @param \Magento\Framework\Session\SessionManager $subject
     * @return void
     */
    public function beforeStart(\Magento\Framework\Session\SessionManager $subject): void
    {
        if ($this->logged) {
            return;
        }

        // Only care about GET/HEAD asking for HTML
        $accept = (string) $this->request->getHeader('Accept');
        $isHtml = ($this->request->isGet() || $this->request->isHead())
                  && ($accept !== '' && str_contains($accept, 'text/html'));

        if (!$isHtml) {
            return;
        }

        // Skip sensitive paths (checkout, cart, customer, APIs, admin, etc.)
        $uri  = (string) ($this->request->getRequestUri() ?? '');
        $path = parse_url($uri, PHP_URL_PATH) ?? '';
        if ($path !== '' && preg_match(
            '#^/(customer|checkout|cart|sales|wishlist|rest|graphql|v1|V1|admin|beheer|customer/section)#'
            , $path
        )) {
            return;
        }

        // Full action name if available (RequestInterface may not have it)
        $fa = method_exists($this->request, 'getFullActionName')
            ? (string) $this->request->getFullActionName()
            : '';

        // Compact backtrace (limit to avoid huge lines)
        $bt    = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 18);
        $stack = [];
        foreach ($bt as $t) {
            $cls = $t['class']    ?? '';
            $fn  = $t['function'] ?? '';
            $fl  = $t['file']     ?? '';
            $ln  = $t['line']     ?? 0;
            $sig = $cls ? ($cls . '::' . $fn) : $fn;
            if ($fl) {
                $sig .= ' @ ' . $fl . ':' . $ln;
            }
            $stack[] = $sig;
        }

        $line = sprintf(
            "[%s] SESSION-START %s %s | %s\n",
            gmdate('c'),
            ($fa !== '' ? $fa : ''),
            $uri,
            implode(' <- ', $stack)
        );

        // 1) Best-effort via Magento logger (NOTICE; may be filtered in prod)
        try {
            if (method_exists($this->logger, 'notice')) {
                $this->logger->notice(trim($line));
            } else {
                $this->logger->info(trim($line));
            }
        } catch (\Throwable $e) {
            // ignore logger failures
        }

        // 2) Always log to /tmp to bypass Magento handlers/permissions
        //    (works even if var/log is restricted)
        try {
            @error_log($line, 3, '/tmp/sessionwatch.log');
        } catch (\Throwable $e) {
            // ignore file write failures
        }

        $this->logged = true;
    }
}
