@php
    $baseClass = "disabled:opacity-75 uppercase rounded-md inline-flex items-center gap-4 " . $tint;

    switch ($size) {
        case 'sm':
            $baseClass .= " px-2 py-1 text-xs";
            break;
        case 'md':
            $baseClass .= " px-4 py-2 text-sm";
            break;
        case 'lg':
            $baseClass .= " px-8 py-4 text-sm";
            break;
        
        default:
            $baseClass .= " px-4 py-2 text-sm";
            break;
    }
@endphp

<{{ $type }} {!! $type == "a" ? 'href=' . $href . '' : '' !!} class="{{ $class . " " . $baseClass }}" {!! $attrs !!}>
    {{ $slot }}
</{{ $type }}>