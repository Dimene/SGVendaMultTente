import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function usePermission() {
    const page = usePage();

    const user = computed(() => page.props.usuario);


    // return user;
    /**
     * Verifica se o utilizador possui uma determinada permissão.
     *
     * Admin tem acesso total.
     */
    const temPermissao = (permissao: string): boolean => {
      
        if (!user.value) {
            return false;
        }
        

        // Administrador tem acesso total
        const isAdmin = user.value.roles?.some(
            (role: any) => role.name === 'Admin'
        );

        

        if (isAdmin) {
            return true;
        }


         const todas = user.value.roles.flatMap(
        (role: any) =>
            (role.permissions ?? []).map(
                (permission: any) => permission.name
            )
    );

   
        // Verifica a permissão atribuída
        return todas.includes(permissao) ?? false;
    };

    /**
     * Alias para ficar semelhante ao Laravel:
     *
     * can('venda-show')
     */
    const can = (permissao: string): boolean => {
        return temPermissao(permissao);
    };

    /**
     * Verifica se possui pelo menos uma das permissões.
     */
    const temAlgumaPermissao = (permissoes: string[]): boolean => {
        return permissoes.some(
            permissao => temPermissao(permissao)
        );
    };

    /**
     * Verifica se possui todas as permissões.
     */
    const temTodasPermissoes = (permissoes: string[]): boolean => {
        return permissoes.every(
            permissao => temPermissao(permissao)
        );
    };

    return {
        user,
        can,
        temPermissao,
        temAlgumaPermissao,
        temTodasPermissoes,
    };
}