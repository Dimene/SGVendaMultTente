import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useEmpresa() {
    const page = usePage()

    const empresa = computed(() => page.props.empresa || {})

    const nomeEmpresa = computed(() =>
        empresa.value.nome_fantasia || empresa.value.nome || 'ERP System'
    )

    // Normaliza o caminho do logo (aceita "logos/x.png", "/storage/x.png", URL completo)
    const logoUrl = computed(() => {
        const logo = empresa.value.logo
        if (!logo) return null
        const s = String(logo).trim()
        if (!s) return null
        if (s.startsWith('http://') || s.startsWith('https://')) return s
        if (s.startsWith('/')) return s
        if (s.startsWith('storage/')) return `/${s}`
        return `/storage/${s}`
    })

    return { empresa, nomeEmpresa, logoUrl }
}