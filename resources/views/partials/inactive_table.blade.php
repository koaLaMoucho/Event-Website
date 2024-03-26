<thead>
    <tr>
        <th>Email</th>
        <th>Name</th>
        <th>Actions</th>
    </tr>
</thead>
<tbody>
@foreach ($inactiveUsers as $user)
    <tr id="inactive_user_row_{{ $user->user_id }}">
        <td>{{ $user->email }}</td>
        <td>{{ $user->name }}</td>
        <td>  
            <button class="activate-btn" data-user-id="{{ $user->user_id }}">Activate</button>
        </td>
    </tr>
@endforeach
</tbody>

