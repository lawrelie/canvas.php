<?php
namespace Lawrelie\Canvas;
use GdImage, Throwable;
class Canvas {
    private int $sx;
    private int $sy;
    public function __construct(private GdImage $image, private int $dpi = 96) {
        $this->sx = \imagesx($this->image);
        $this->sy = \imagesy($this->image);
    }
    public function __get(string $name): mixed {
        return match ($name) {'image', 'sx', 'sy' => $this->$name, default => null};
    }
    public function __isset(string $name): bool {
        return !\is_null($this->__get($name));
    }
    public function create(?GdImage $image = null, int $dpi = 0, ?string $className = null): self {
        $args = [!$image ? $this->image : $image, !$dpi ? $this->dpi : $dpi];
        return !empty($className) ? new $className(...$args) : new static(...$args);
    }
    public function createFromFile(string $file): self {
        $imagecreatefrom = 'imagecreatefrom' . \pathinfo($file, \PATHINFO_EXTENSION);
        return $this->create($imagecreatefrom($file));
    }
    public function crop(float $min, float $max, float $offsetX = 50, float $offsetY = 50): self {
        $s = $this->sx / $this->sy;
        if (($min <= $s && $s <= $max) || ($min >= $s && $s >= $max)) {
            return $this;
        }
        list($width, $height) = match (($s < $min && $min <= $max) || ($s > $min && $min >= $max)) {
            true => [\min($this->sx, $this->sy * $min), \min($this->sx / $min, $this->sy)],
            default => [\min($this->sx, $this->sy * $max), \min($this->sx / $max, $this->sy)],
        };
        return $this->create(\imagecrop($this->image, ['x' => ($this->sx - $width) * $offsetX / 100, 'y' => ($this->sy - $height) * $offsetY / 100, 'width' => $width, 'height' => $height]));
    }
    public function draw(string $type): bool {
        try {
            \header('Content-Type: image/' . $type);
            $draw = '\image' . $type;
            return $draw($this->image);
        } catch (Throwable) {}
        return false;
    }
    public function formatMultilineText(float $maxWidth, float $maxHeight, float $fontPt, float $angle, string $fontFilename, string $string, array $options = [], string $overflow = '…'): array {
        $i = 0;
        $j = \mb_strlen($string);
        $textbox = '';
        while ($i < $j) {
            $lineLength = 0;
            while (true) {
                $before = \mb_substr($string, $i, $lineLength);
                $after = \mb_substr($string, $i + $lineLength);
                $isAfterPiPs = !!\preg_match('/[\p{Pi}\p{Ps}]$/u', $before);
                if (!!\preg_match('/^\s*?\R/u', $after, $m)) {
                    $length = \mb_strlen($m[0]);
                    $string = \mb_substr($string, 0, $i + $lineLength) . \mb_substr($string, $i + $lineLength + $length);
                    $j -= $length;
                    break;
                } elseif (!!\preg_match('/^[\p{Ll}\p{Lm}\p{Lt}\p{Lu}]+/u', $after, $m)) {
                    $length = \mb_strlen($m[0]);
                    $bbox = \imageftbbox($fontPt, $angle, $fontFilename, $textbox . \mb_substr($string, $i, $lineLength + $length), $options);
                    $bboxX = [$bbox[0], $bbox[2], $bbox[4], $bbox[6]];
                    if ($maxWidth >= \max(...$bboxX) - \min(...$bboxX)) {
                        $lineLength += $length;
                        continue;
                    } elseif ($isAfterPiPs || !$lineLength) {
                        $lineLength += $length;
                    }
                    break;
                } elseif (
                    !!\preg_match(
                        '/^(?:[\p{Sc}＃№]*(?:(?!\p{Han})\p{N})+[°′″℃￠％‰㏋ℓ㌃㌍㌔㌘㌢㌣㌦㌧㌫㌶㌻㍉㍊㍍㍑㍗㎎㎏㎜㎝㎞㎡㏄]*|[\p{Pe}\p{Pf}‐〜゠–\-~！？‼⁇⁈⁉!?・：；:;。．\.、，,ヽヾゝゞ々〻ーぁぃぅぇぉァィゥェォっゃゅょゎゕゖッャュョヮヵヶㇰㇱㇲㇳㇴㇵㇶㇷㇸㇹㇺㇻㇼㇽㇾㇿㇷ゚\p{Pd}…‥\p{Sc}＃№°′″℃￠％‰㏋ℓ㌃㌍㌔㌘㌢㌣㌦㌧㌫㌶㌻㍉㍊㍍㍑㍗㎎㎏㎜㎝㎞㎡㏄]+)/u',
                        $after,
                        $m,
                    )
                ) {
                    $length = \mb_strlen($m[0]);
                    $bbox = \imageftbbox($fontPt, $angle, $fontFilename, $textbox . \mb_substr($string, $i, $lineLength + $length), $options);
                    $bboxX = [$bbox[0], $bbox[2], $bbox[4], $bbox[6]];
                    if ($maxWidth >= \max(...$bboxX) - \min(...$bboxX)) {
                        $lineLength += $length;
                        continue;
                    } elseif ($isAfterPiPs || !$lineLength) {
                        $lineLength += $length;
                    } else {
                        $lineLength--;
                    }
                    break;
                } elseif (!!\preg_match('/^[\p{Pi}\p{Ps}]+(?=[^\p{Pi}\p{Ps}])/u', $after, $m)) {
                    $length = \mb_strlen($m[0]);
                    $bbox = \imageftbbox($fontPt, $angle, $fontFilename, $textbox . \mb_substr($string, $i, $lineLength + $length + 1), $options);
                    $bboxX = [$bbox[0], $bbox[2], $bbox[4], $bbox[6]];
                    if ($maxWidth >= \max(...$bboxX) - \min(...$bboxX)) {
                        $lineLength += $length;
                        continue;
                    } elseif ($isAfterPiPs || !$lineLength) {
                        $lineLength += $length;
                    }
                    break;
                } elseif ($isAfterPiPs) {
                    $lineLength++;
                    continue;
                } elseif ($i + $lineLength + 1 > $j) {
                    break;
                }
                $bbox = \imageftbbox($fontPt, $angle, $fontFilename, $textbox . \mb_substr($string, $i, $lineLength + 1), $options);
                $bboxX = [$bbox[0], $bbox[2], $bbox[4], $bbox[6]];
                if ($maxWidth >= \max(...$bboxX) - \min(...$bboxX)) {
                    $lineLength++;
                    continue;
                }
                break;
            }
            if (!$lineLength) {
                break;
            }
            $line = \mb_substr($string, $i, $lineLength);
            $lineSuffix = '';
            $i += $lineLength;
            while (!!$lineLength) {
                $bbox = \imageftbbox($fontPt, $angle, $fontFilename, $textbox . \mb_substr($line, 0, $lineLength) . $lineSuffix, $options);
                $bboxX = [$bbox[0], $bbox[2], $bbox[4], $bbox[6]];
                if ($maxWidth >= \max(...$bboxX) - \min(...$bboxX)) {
                    $bboxY = [$bbox[1], $bbox[3], $bbox[5], $bbox[7]];
                    if ($maxHeight < \max(...$bboxY) - \min(...$bboxY)) {
                        $textbox = \preg_replace('/\S(?=\n$)/u', $overflow, $textbox);
                        break 2;
                    }
                    $textbox .= \mb_substr($line, 0, $lineLength) . $lineSuffix . "\n";
                    break;
                }
                $lineLength--;
                $lineSuffix = $overflow;
            }
        }
        $bbox = \imageftbbox($fontPt, $angle, $fontFilename, $textbox, $options);
        $bboxX = [$bbox[0], $bbox[2], $bbox[4], $bbox[6]];
        $bboxY = [$bbox[1], $bbox[3], $bbox[5], $bbox[7]];
        return ['text' => $textbox, 'bbox' => $bbox, 'width' => \max(...$bboxX) - \min(...$bboxX), 'height' => \max(...$bboxY) - \min(...$bboxY)];
    }
    public function formatSinglelineText(float $maxWidth, float $fontPt, float $angle, string $fontFilename, string $string, array $options = [], string $overflow = '…'): array {
        $j = \mb_strlen($string);
        $textbox = '';
        for ($i = 0; $i < $j; $i++) {
            $textbox = \mb_substr($string, 0, $j - $i) . (!$i ? '' : $overflow);
            $bbox = \imageftbbox($fontPt, $angle, $fontFilename, $string, $options);
            $bboxX = [$bbox[0], $bbox[2], $bbox[4], $bbox[6]];
            $width = \max(...$bboxX) - \min(...$bboxX);
            if ($maxWidth >= $width) {
                $bboxY = [$bbox[1], $bbox[3], $bbox[5], $bbox[7]];
                return ['text' => $textbox, 'bbox' => $bbox, 'width' => $width, 'height' => \max(...$bboxY) - \min(...$bboxY)];
            }
        }
        return ['text' => '', 'bbox' => [0, 0, 0, 0, 0, 0, 0, 0], 'width' => 0, 'height' => 0];
    }
    public function linespacingFrom(float $linespacing): float {
        return $linespacing / 1.5;
    }
    public function px2pt(int $px): float {
        return $px / ($this->dpi / 72);
    }
}
