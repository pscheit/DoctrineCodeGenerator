# Code Generation

In general code-generation just moves the inability to correctly abstract code into a layer that simplifies the downsides of writing lots of code by hand. However there are more use-cases for code-generation:

* Transforming existing models (XML, UML, CSV, whatever) from one to another representation.
* Generating boiler-plate code that is impossible to abstract nicely (`__get`/`__set`/`__call` alternatives)
* Generating small bits of repetitive code that is not abstractable in PHP like specialized getters/setters, bidirectional method handlers.

However the current approach for code-generation in Doctrine is fail. A single class organizes the code generation and has no sane means for extension. Templating based code-generation mechanism come into mind, but they only make the problem larger. Templates are not useful for code-generation because they dont offer manipulation and composition of different concerns, they only allow a linear direction of templates that are stacked on top of each other.

Proper Code-Generation uses an AST that can be manipulated by the developer at any given point during the process. Manipulation of the AST can be triggered by events. The code-generator is feed by multiple sources of input that all operate on a large AST. The first source builds the general class layout, the second one adds to it and so on. The order of input given to the AST generator is relevant. It should be possible to write compiling/running code and then link this into the generated source code as if you literally write "traits". This should feel sort of like aspect-oriented programming where you designate some piece of code to be "used" by the code-generator during the generation process.

The problem of code-generators is that they leave you with thousands of lines of untested code that is to be integrated in your application. This often leaves you with a huge gap of tested to untested code that is impossible to close.

---
pscheit:

did you thought about writing auto-generated-tests for your auto generated classes? What if every practial AST manipulation would add its own testcase to a standardized Unit-Test (with the same API).
For example think of the bi-directional method handlers for doctrine. Wouldn't it be possible to write automated tests for the auto-generated accessors? If not, would it be at least possible to provide method stubs, which would be complete by the developer itself, when intergration the auto-generated code into his application?

---

## Idea

Using nikics PHP Parser library we generate code using an AST. The code is generated from a set of input sources. Events are triggered when code blocks are generated:

* Class
* Property
* Method
    * GetterMethod
    * SetterMethod
    * Constructor
* Function

Event Handlers can register to each events and either:

* Manipulate the AST
* Trigger more specialized events

A configuration for the code-generator would look like:

    @@@ yml

    generator:
      input:
        doctrine-mapping: from-database
      events:
        Doctrine\CodeGenerator\EventHandler\GetterSetterListener: ~
        Doctrine\CodeGenerator\EventHandler\ReadOnlyEntityValueObjectListener: ~
        Doctrine\CodeGenerator\EventHandler\BidirectionalAssociationListener: ~
        Doctrine\CodeGenerator\EventHandler\DocBlockListener: ~
        Doctrine\CodeGenerator\EventHandler\DoctrineAnnotations: ~
        Doctrine\CodeGenerator\EventHandler\ImmutableObjects:
          - "ImmutableClass1"
      output:
        codingStandard: Symfony

PHP Parser does not provide a nice API to manipulate the AST. Because this is a major operation we need to create an API (and contribute it back!) for this. A managable solution for developers would be a kind of DOM like approach with a jQuery-like API for manipulation. A selector language can filter for specific code elements and then manipulate them:

    $tree->find('Class[name="ImmutableClass"] Property[name="set*"]')->remove();
    $returnStmt = $tree->find("Return");
    $returnStmt->before("$this->assertFoo();");

As you can see in the last block there is also support for injecting strings into the AST. Using PHP Parser these bits are parsed into an AST aswell and put at the specific locations in the AST.

## Domain Specific Language for building Code Snippets

the manipulation of the AST seems to be the critical part of the api of the code generator for me. I think the jQuery approach is a good idea to infiltrate code into an already huge AST which is not very easy to traverse.
In addition I would create a small Domain Specific Language (DSL) for writing PHP Code in PHP Code. Otherwise you will get crazy escaping $ and backslashes and losing a lot of readability.
Recently i experiment a lot with closures / namespace functions to write a more declarative and readable DSLs:

    <?php
    $cb = new CodeBuilder();
    
    $class(
      $method(
        'addUser',
        $param('$user', $type('Entities\User')),
        $body(
          $if($objectCall($instanceVariable('users'), 'contains', $argument('$user')),
              $objectCall($instanceVariable('users'), 'add', $argument('$user'))
             ),
          $returnStmt($instance())
        )
      )
    );
    
    
    // =>
    public function addUser(Entities\user $user) {
      if ($this->users->contains($user)) {
        $this->users->add($user);
      }
      return $this;
    }

this is verbose and not easy to document. But it lets you stack Code-Building Code and It won't end in a string concatenating and escaping massacre.