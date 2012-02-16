<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\CodeGenerator\Listener;

use Doctrine\CodeGenerator\GeneratorEvent;

/**
 * Each property is turned to protected and getters/setters are added.
 */
class DocListener extends AbstractCodeListener
{
    public function onGenerateProperty(GeneratorEvent $event)
    {
        $node = $event->getNode();
        $node->setDocComment(<<<EPM
/**
 * @var mixed
 */
EPM
);
    }

    public function onGenerateGetter(GeneratorEvent $event)
    {
        $node = $event->getNode();
        $propertyName = lcfirst(substr($node->name, 3));
        $node->setDocComment(<<<EPM
/**
 * Return $propertyName
 *
 * @return mixed
 */
EPM
);
    }

    public function onGenerateSetter(GeneratorEvent $event)
    {
        $node = $event->getNode();
        $propertyName = $node->params[0]->name;
        $type = $node->params[0]->type ?: "mixed";
        $node->setDocComment(<<<EPM
/**
 * Set $propertyName
 *
 * @param $type \$$propertyName
 */
EPM
);
    }
}

