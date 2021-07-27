# canvas.php
PHP>=8.0 で画像を編集する

```php
include 'path/to/canvas.php';
$canvas = new Lawrelie\Canvas\Canvas(imagecreatetruecolor(1920, 1080));
$canvas->draw('webp');
```
