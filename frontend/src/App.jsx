import { useEffect, useMemo, useState } from 'react'
import './index.css'

const API_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api'
const tagColors = ['#2563eb', '#059669', '#dc2626', '#7c3aed', '#d97706']

async function api(path, options = {}) {
  const response = await fetch(`${API_URL}${path}`, {
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    ...options,
    body: options.body ? JSON.stringify(options.body) : undefined,
  })

  if (response.status === 204) return null

  const data = await response.json()
  if (!response.ok) throw new Error(data.message || 'Request failed')
  return data
}

function TagBadge({ tag }) {
  return (
    <span className="tag" style={{ '--tag-color': tag.color }}>
      {tag.name}
    </span>
  )
}

function MemberAvatar({ member }) {
  const initials = member.name
    .split(' ')
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()

  return <span className="avatar" title={member.name}>{initials}</span>
}

function DueDate({ value }) {
  if (!value) return null

  const today = new Date(new Date().toDateString())
  const date = new Date(`${value}T00:00:00`)
  return <span className={date < today ? 'due overdue' : 'due'}>{value}</span>
}

function CardModal({ card, board, onClose, onChanged }) {
  const [title, setTitle] = useState(card.title)
  const [description, setDescription] = useState(card.description || '')
  const [dueDate, setDueDate] = useState(card.due_date || '')

  async function save(event) {
    event.preventDefault()
    await api(`/cards/${card.id}`, {
      method: 'PATCH',
      body: { title, description, due_date: dueDate || null },
    })
    await onChanged()
    onClose()
  }

  async function toggleTag(tag) {
    const attached = (card.tags || []).some((item) => item.id === tag.id)
    await api(`/cards/${card.id}/tags/${tag.id}`, { method: attached ? 'DELETE' : 'POST' })
    await onChanged()
  }

  async function toggleMember(member) {
    const assigned = (card.members || []).some((item) => item.id === member.id)
    await api(`/cards/${card.id}/members/${member.id}`, { method: assigned ? 'DELETE' : 'POST' })
    await onChanged()
  }

  return (
    <div className="modal-backdrop">
      <form className="modal" onSubmit={save}>
        <header className="modal-header">
          <h2>Edit card</h2>
          <button type="button" className="ghost" onClick={onClose}>Close</button>
        </header>

        <label>
          Title
          <input value={title} onChange={(event) => setTitle(event.target.value)} />
        </label>

        <label>
          Description
          <textarea value={description} onChange={(event) => setDescription(event.target.value)} />
        </label>

        <label>
          Due date
          <input type="date" value={dueDate} onChange={(event) => setDueDate(event.target.value)} />
        </label>

        <div className="modal-grid">
          <section>
            <h3>Tags</h3>
            {(board.tags || []).map((tag) => (
              <label key={tag.id} className="check-row">
                <input
                  type="checkbox"
                  checked={(card.tags || []).some((item) => item.id === tag.id)}
                  onChange={() => toggleTag(tag)}
                />
                {tag.name}
              </label>
            ))}
          </section>

          <section>
            <h3>Members</h3>
            {(board.members || []).map((member) => (
              <label key={member.id} className="check-row">
                <input
                  type="checkbox"
                  checked={(card.members || []).some((item) => item.id === member.id)}
                  onChange={() => toggleMember(member)}
                />
                {member.name}
              </label>
            ))}
          </section>
        </div>

        <button type="submit">Save card</button>
      </form>
    </div>
  )
}

function KanbanCard({ card, onOpen, onDragStart }) {
  return (
    <article
      className="card"
      draggable
      onDragStart={(event) => {
        event.dataTransfer.effectAllowed = 'move'
        onDragStart(card)
      }}
      onDoubleClick={() => onOpen(card)}
    >
      <button type="button" className="card-title" onClick={() => onOpen(card)}>
        {card.title}
      </button>
      <div className="tags">
        {(card.tags || []).map((tag) => <TagBadge key={tag.id} tag={tag} />)}
      </div>
      <div className="card-meta">
        <DueDate value={card.due_date} />
        <div className="avatars">
          {(card.members || []).map((member) => <MemberAvatar key={member.id} member={member} />)}
        </div>
      </div>
    </article>
  )
}

function ListColumn({ list, onCreateCard, onOpenCard, onDropCard, onDragStart }) {
  const [title, setTitle] = useState('')

  function submit(event) {
    event.preventDefault()
    if (!title.trim()) return
    onCreateCard(list.id, title.trim())
    setTitle('')
  }

  return (
    <section
      className="list"
      onDragOver={(event) => event.preventDefault()}
      onDrop={() => onDropCard(list)}
    >
      <header className="list-header">
        <h2>{list.name}</h2>
        <span>{(list.cards || []).length}</span>
      </header>

      <div className="cards">
        {(list.cards || []).map((card) => (
          <KanbanCard key={card.id} card={card} onOpen={onOpenCard} onDragStart={onDragStart} />
        ))}
      </div>

      <form className="inline-form" onSubmit={submit}>
        <input value={title} onChange={(event) => setTitle(event.target.value)} placeholder="New card" />
        <button type="submit">Add</button>
      </form>
    </section>
  )
}

export default function App() {
  const [boards, setBoards] = useState([])
  const [board, setBoard] = useState(null)
  const [selectedBoardId, setSelectedBoardId] = useState(null)
  const [selectedCard, setSelectedCard] = useState(null)
  const [draggedCard, setDraggedCard] = useState(null)
  const [boardName, setBoardName] = useState('')
  const [listName, setListName] = useState('')
  const [tagName, setTagName] = useState('')
  const [memberName, setMemberName] = useState('')
  const [error, setError] = useState('')

  const currentCard = useMemo(() => {
    if (!selectedCard || !board) return null
    return board.lists
      .flatMap((list) => list.cards || [])
      .find((card) => card.id === selectedCard.id)
  }, [board, selectedCard])

  async function loadBoards() {
    const data = await api('/boards')
    setBoards(data)
    if (!selectedBoardId && data[0]) setSelectedBoardId(data[0].id)
  }

  async function loadBoard(boardId = selectedBoardId) {
    if (!boardId) return
    setBoard(await api(`/boards/${boardId}`))
  }

  useEffect(() => {
    loadBoards().catch((caught) => setError(caught.message))
  }, [])

  useEffect(() => {
    loadBoard().catch((caught) => setError(caught.message))
  }, [selectedBoardId])

  async function createBoard(event) {
    event.preventDefault()
    if (!boardName.trim()) return
    const created = await api('/boards', { method: 'POST', body: { name: boardName.trim() } })
    setBoardName('')
    setSelectedBoardId(created.id)
    await loadBoards()
  }

  async function createList(event) {
    event.preventDefault()
    if (!listName.trim() || !board) return
    await api(`/boards/${board.id}/lists`, { method: 'POST', body: { name: listName.trim() } })
    setListName('')
    await loadBoard()
  }

  async function createCard(listId, title) {
    await api(`/lists/${listId}/cards`, { method: 'POST', body: { title } })
    await loadBoard()
  }

  async function createTag(event) {
    event.preventDefault()
    if (!tagName.trim() || !board) return
    const color = tagColors[(board.tags || []).length % tagColors.length]
    await api(`/boards/${board.id}/tags`, { method: 'POST', body: { name: tagName.trim(), color } })
    setTagName('')
    await loadBoard()
  }

  async function createMember(event) {
    event.preventDefault()
    if (!memberName.trim() || !board) return
    const member = await api('/members', { method: 'POST', body: { name: memberName.trim() } })
    await api(`/boards/${board.id}/members/${member.id}`, { method: 'POST' })
    setMemberName('')
    await loadBoard()
  }

  async function dropCard(list) {
    if (!draggedCard) return
    await api(`/cards/${draggedCard.id}/move`, {
      method: 'POST',
      body: { list_id: list.id, position: (list.cards || []).length + 1 },
    })
    setDraggedCard(null)
    await loadBoard()
  }

  return (
    <main className="app-shell">
      <aside className="sidebar">
        <h1>Kanban</h1>
        <form className="stack" onSubmit={createBoard}>
          <input value={boardName} onChange={(event) => setBoardName(event.target.value)} placeholder="New board" />
          <button type="submit">Create board</button>
        </form>
        <nav className="board-list">
          {boards.map((item) => (
            <button
              key={item.id}
              type="button"
              className={item.id === selectedBoardId ? 'active board-button' : 'board-button'}
              onClick={() => setSelectedBoardId(item.id)}
            >
              {item.name}
            </button>
          ))}
        </nav>
      </aside>

      <section className="workspace">
        {error ? <p className="error">{error}</p> : null}
        {board ? (
          <>
            <header className="workspace-header">
              <div>
                <h2>{board.name}</h2>
                <p>{(board.lists || []).length} lists</p>
              </div>
            </header>

            <section className="toolbar">
              <form className="inline-form" onSubmit={createList}>
                <input value={listName} onChange={(event) => setListName(event.target.value)} placeholder="New list" />
                <button type="submit">Create list</button>
              </form>
              <form className="inline-form" onSubmit={createTag}>
                <input value={tagName} onChange={(event) => setTagName(event.target.value)} placeholder="New tag" />
                <button type="submit">Create tag</button>
              </form>
              <form className="inline-form" onSubmit={createMember}>
                <input
                  value={memberName}
                  onChange={(event) => setMemberName(event.target.value)}
                  placeholder="New member"
                />
                <button type="submit">Create member</button>
              </form>
            </section>

            <section className="kanban">
              {(board.lists || []).map((list) => (
                <ListColumn
                  key={list.id}
                  list={list}
                  onCreateCard={createCard}
                  onOpenCard={setSelectedCard}
                  onDragStart={setDraggedCard}
                  onDropCard={dropCard}
                />
              ))}
            </section>
          </>
        ) : (
          <section className="empty-state">
            <h2>Create a board to begin</h2>
          </section>
        )}
      </section>

      {currentCard ? (
        <CardModal
          card={currentCard}
          board={board}
          onClose={() => setSelectedCard(null)}
          onChanged={() => loadBoard()}
        />
      ) : null}
    </main>
  )
}
