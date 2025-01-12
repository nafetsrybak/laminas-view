# HtmlObject

The [HTML `<object>` element](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/object) is used for embedding external media in web pages. The object view helpers take care of embedding media with minimum effort.

There are two initial Object helpers:

- `htmlObject()` Generates markup for embedding a custom Object.
- `htmlPage()` Generates markup for embedding other (X)HTML pages.

All of these helpers share a similar interface. For this reason, this
documentation will only contain examples of two of these helpers.

## HtmlPage helper

Embedding an external HTML page in your page using the helper only requires the resource URI.

```php
<?= $this->htmlPage('https://www.example.com/some-page.html'); ?>
```

This outputs the following HTML:

```html
<object data="https://www.example.com/some-page.html"
        type="text/html"
        classid="clsid:25336920-03F9-11CF-8FD0-00AA00686F13">
</object>
```

Additionally, you can specify attributes, parameters, and content that can be
rendered along with the `<object>`. This will be demonstrated using the
`htmlObject()` helper.

## Customizing the object by passing additional arguments

The first argument in the object helpers is always required. It is the URI to
the resource you want to embed. The second argument is only required in the
`htmlObject()` helper. The other helpers already contain the correct value for
this argument. The third argument is used for passing along attributes to the
object element. It only accepts an array with key-value pairs. `classid` and
`codebase` are examples of such attributes. The fourth argument also only takes
a key-value array and uses them to create `<param>` elements. You will see an
example of this shortly. Lastly, there is the option of providing additional
content to the object. The following example utilizes all arguments.

```php
echo $this->htmlObject(
    '/path/to/file.ext',
    'mime/type',
    [
        'attr1' => 'aval1',
        'attr2' => 'aval2',
    ],
    [
        'param1' => 'pval1',
        'param2' => 'pval2',
    ],
    'some content'
);
```

This would output:

```html
<object data="/path/to/file.ext" type="mime/type"
    attr1="aval1" attr2="aval2">
    <param name="param1" value="pval1" />
    <param name="param2" value="pval2" />
    some content
</object>
```
