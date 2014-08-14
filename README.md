# IntaroMemcachedTagsBundle #

## About ##
The Memcached Tags Bundle allows to add tags for doctrine query result cache and clear result cache by tags. Based on [LswMemcacheBundle](https://github.com/LeaseWeb/LswMemcacheBundle)

## Installation ##
Require the bundle in your composer.json file:

````
{
    "require": {
        "intaro/memcached-tags-bundle": "dev-master"
    }
}
```

Register the bundle:

```php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        new Intaro\MemcachedTagsBundle\IntaroMemcachedTagsBundle(),
    );
}
```

Install the bundle:

```
$ composer update intaro/memcached-tags-bundle
```

## Usage ##

### Query ###

Create new Query with necessary cache life time and cache tags:

```php
    $em = $container->get('doctrine')->getManager();
    $em->createQuery('SELECT book FROM AcmeHelloBundle:Book book', 3600, [
        Acme\HelloBundle\Entity\Book
    ]);
```

### NativeQuery ###

NativeQuery with cache tags works same as Query.

```php
    $em = $container->get('doctrine')->getManager();
    $em->createNativeQuery('SELECT * FROM book', $rsm, 3600, [
        Acme\HelloBundle\Entity\Book
    ]);
```

### QueryBuilder ###

```php
    $em = $container->get('doctrine')->getManager();
    $builder = $em->createQueryBuilder()
        ->select('book')->from('AcmeHelloBundle:Book', 'book')
        ->useResultCache(true, 3600) //enable result cache and set cache life time
        ->setCacheTags([Acme\HelloBundle\Entity\Book])
        ->join('book.author', 'author')
        ->addCacheTag(Acme\HelloBundle\Entity\Author);

    if ($disableTags) {
        $builder->clearCacheTags();
    }

    $builder->getQuery()->getResult();
```

### Clear cache ###

```php
    $em = $container->get('doctrine')->getManager();
    $em->getRepository('AcmeHelloBundle:Book')->clearEntityCache();
    // or
    $em->tagClear('Acme\HelloBundle\Entity\Book');

    $book = $em->getRepository('AcmeHelloBundle:Book')->find($id);
    $em->getRepository('AcmeHelloBundle:Book')->clearEntityIdCache($book->getId());
    // or
    $em->tagClear('Acme\HelloBundle\Entity\Book:' . $book->getId());
```

On entity insertions, update and deletes automatically clears cache for changed class names and changed entity id.

```php
    $em = $container->get('doctrine')->getManager();
    $book = $em->getRepository('AcmeHelloBundle:Book')->find(25);
    $book->setName('New book');
    $em->merge($book);
    $em->flush();
    // Tags Acme\HelloBundle\Entity\Book and Acme\HelloBundle\Entity\Book:25 are cleared
```
