Mock builder for PHP
=======================

For now library is not ready - code needs major refactoring (now we have prof of concept code which evolved).
It also lacks some of crucial mock functionalities - now it is more like Stub library, It's impossible to assert if method
X was called, how many times it was called or to check if it was called after method Y.


But why?
========

Let say u need a mock of Book which have method getTitle returning 'title' and method getAuthor returning Author with method
getName returning 'author'. To do this you must write at least 10 lines of bloat (and the worst part isn't about writing but about reading).
If you need more complex behaviour then things are getting even worse. This project is an experiment which shows that mocking/stubing can be done better way.

```php
  $author = $this->getMock('Author');

  $author->expects($this->any())
          ->method('getName')
          ->will($this->returnValue('author'));

  $book = $this->getMock('Book');

  $book->expects($this->any())
       ->method('getTitle')
       ->will($this->returnValue('title'));

  $book->expects($this->any())
       ->method('getAuthor')
       ->will($this->returnValue($author));

  // VS

  $this->getSMock('Book
                       getTitle -> "title"
                       getAuthor -> Author
                           getName -> "author"');
```

More examples in tests\MockTest.php

