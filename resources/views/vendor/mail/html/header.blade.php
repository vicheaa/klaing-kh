@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">{{ trim($slot) === 'Laravel' ? 'Klaing KH' : $slot }}</a>
</td>
</tr>
