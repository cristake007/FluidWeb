<?php

declare(strict_types=1);

namespace FluidWeb\PHPStan;

use App\Kernel;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Methods\AlwaysUsedMethodExtension;

final class SymfonyKernelAlwaysUsedMethodExtension implements AlwaysUsedMethodExtension
{
    public function isAlwaysUsed(MethodReflection $methodReflection): bool
    {
        return Kernel::class === $methodReflection->getDeclaringClass()->getName()
            && 'getAllowedEnvs' === $methodReflection->getName();
    }
}
